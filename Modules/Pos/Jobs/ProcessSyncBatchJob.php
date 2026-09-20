<?php

declare(strict_types=1);

namespace Modules\Pos\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Pos\App\Models\Order;
use Modules\Pos\App\Models\OrderItem;
use Modules\Pos\App\Models\Payment;
use Modules\Pos\App\Models\Product;
use Modules\Pos\App\Models\StockLedgerEntry;
use Modules\Pos\App\Models\SyncBatch;
use Modules\Pos\Events\TenantDataSynced;
use Throwable;

final class ProcessSyncBatchJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [5, 15, 60, 300];

    /**
     * @param  array<int, array<string, mixed>>  $orders  Validated order payloads
     */
    public function __construct(
        public readonly string $batchId,
        public readonly array $orders,
    ) {}

    /**
     * Unique per batch_id for the lifetime it sits on the queue — a second
     * dispatch for the same batch (e.g. a duplicate HTTP retry that slipped
     * past the controller's SyncBatch::find check) is dropped rather than
     * double-processed.
     */
    public function uniqueId(): string
    {
        return $this->batchId;
    }

    public function handle(): void
    {
        $batch = SyncBatch::findOrFail($this->batchId);
        $batch->update(['status' => 'processing']);

        $summary = ['orders_processed' => 0, 'invoice_numbers' => [], 'mpesa_pending' => []];

        try {
            DB::transaction(function () use (&$summary): void {
                foreach ($this->orders as $orderPayload) {
                    $order = $this->syncOrder($orderPayload);
                    $summary['orders_processed']++;
                    $summary['invoice_numbers'][$order->local_reference ?? $order->id] = $order->invoice_number;

                    if ($order->payments()->where('method', 'mpesa')->where('status', 'pending')->exists()) {
                        $summary['mpesa_pending'][] = $order->id;
                    }
                }
            });

            $batch->update([
                'status' => 'processed',
                'processed_at' => now(),
                'result_summary' => $summary,
            ]);

            foreach ($summary['mpesa_pending'] as $orderId) {
                $payment = Payment::where('order_id', $orderId)->where('method', 'mpesa')->firstOrFail();
                InitiateMpesaStkPushJob::dispatch($payment->id)->onQueue('mpesa');
            }

            event(new TenantDataSynced($this->batchId, $summary));
        } catch (Throwable $e) {
            Log::error('pos.sync_batch.failed', ['batch_id' => $this->batchId, 'error' => $e->getMessage()]);
            $batch->update(['status' => 'failed', 'error' => $e->getMessage()]);

            throw $e; // let Horizon apply the configured backoff/retry policy
        }
    }

    private function syncOrder(array $payload): Order
    {
        // Idempotent upsert keyed on the client-generated UUIDv7: replaying
        // this job (e.g. after a retry) must never create duplicate orders,
        // duplicate ledger movements, or re-assign an invoice number.
        $order = Order::find($payload['id']);

        if ($order !== null && $order->hasInvoiceNumber()) {
            // Already fully synced in a prior attempt — nothing to do.
            return $order;
        }

        $order ??= new Order(['id' => $payload['id']]);

        $order->fill([
            'local_reference' => $payload['local_reference'] ?? null,
            'device_id' => $payload['device_id'] ?? null,
            'customer_id' => $payload['customer_id'] ?? null,
            'cashier_user_id' => $payload['cashier_user_id'] ?? null,
            'warehouse_id' => $payload['warehouse_id'] ?? null,
            'currency' => $payload['currency'],
            'order_date' => $payload['order_date'],
            'client_created_at' => $payload['client_created_at'],
            'client_updated_at' => $payload['client_updated_at'],
            'version' => $payload['version'],
            'status' => 'awaiting_payment',
            'synced_at' => now(),
        ]);

        // Persist the order row FIRST — order_items and payments carry a
        // foreign key on order_id, so the parent row must exist before any
        // child insert.
        $order->save();

        [$subtotal, $taxTotal] = [0.0, 0.0];

        foreach ($payload['items'] as $itemPayload) {
            $line = $this->syncOrderItem($order, $itemPayload);
            $subtotal += (float) $line->line_subtotal;
            $taxTotal += (float) $line->tax_amount;
        }

        $order->subtotal = $subtotal;
        $order->tax_total = $taxTotal;
        $order->grand_total = $subtotal + $taxTotal;

        // === Server-side authoritative invoice number assignment ===
        // Client devices never generate this. Allocated exactly once, from
        // a Postgres SEQUENCE, guaranteeing monotonic uniqueness even with
        // multiple Horizon workers running across tenants concurrently.
        $sequenceValue = DB::selectOne('SELECT nextval(?) AS n', ['pos_invoice_number_seq'])->n;
        $order->invoice_number = sprintf('INV-%06d', $sequenceValue);
        $order->status = 'paid';
        $order->save();

        $this->syncPayment($order, $payload['payment']);

        return $order;
    }

    private function syncOrderItem(Order $order, array $payload): OrderItem
    {
        $existing = OrderItem::find($payload['id']);
        if ($existing !== null) {
            return $existing;
        }

        $product = Product::findOrFail($payload['product_id']);

        $quantity = (float) $payload['quantity'];
        $unitPrice = (float) $payload['unit_price'];
        $discount = (float) ($payload['discount_amount'] ?? 0);
        $lineSubtotal = ($quantity * $unitPrice) - $discount;
        $taxAmount = round($lineSubtotal * (float) $payload['tax_rate'], 2);

        $item = OrderItem::create([
            'id' => $payload['id'],
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'product_sku_snapshot' => $product->sku,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => $payload['tax_rate'],
            'tax_amount' => $taxAmount,
            'discount_amount' => $discount,
            'line_subtotal' => $lineSubtotal,
            'line_total' => $lineSubtotal + $taxAmount,
            'client_created_at' => $order->client_created_at,
            'client_updated_at' => $order->client_updated_at,
            'version' => 1,
            'synced_at' => now(),
        ]);

        // Append-only ledger movement — outbound sale, negative delta.
        // Guarded by the order_item's own idempotent creation above: if the
        // item already existed, this ledger entry is never duplicated.
        StockLedgerEntry::create([
            'id' => (string) Str::uuid7(),
            'product_id' => $product->id,
            'warehouse_id' => $order->warehouse_id,
            'movement_type' => 'sale',
            'quantity_delta' => -$quantity,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'device_id' => $order->device_id,
            'occurred_at' => $order->order_date,
            'client_created_at' => $order->client_created_at,
            'synced_at' => now(),
            'version' => 1,
        ]);

        return $item;
    }

    private function syncPayment(Order $order, array $payload): Payment
    {
        $existing = Payment::find($payload['id']);
        if ($existing !== null) {
            return $existing;
        }

        return Payment::create([
            'id' => $payload['id'],
            'order_id' => $order->id,
            'method' => $payload['method'],
            // Cash is confirmed instantly; M-Pesa starts pending and is
            // confirmed asynchronously by the callback handler.
            'status' => $payload['method'] === 'cash' ? 'completed' : 'pending',
            'amount' => $payload['amount'],
            'currency' => $order->currency,
            'payer_phone' => $payload['payer_phone'] ?? null,
            'paid_at' => $payload['method'] === 'cash' ? now() : null,
            'client_created_at' => $order->client_created_at,
            'client_updated_at' => $order->client_updated_at,
            'version' => 1,
            'synced_at' => now(),
        ]);
    }
}

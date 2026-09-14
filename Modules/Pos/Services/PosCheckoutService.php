<?php

declare(strict_types=1);

namespace Modules\Pos\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Pos\App\Models\Order;
use Modules\Pos\App\Models\OrderItem;
use Modules\Pos\App\Models\Payment;
use Modules\Pos\App\Models\Product;
use Modules\Pos\App\Models\StockLedgerEntry;
use Modules\Pos\Jobs\InitiateMpesaStkPushJob;
use Modules\Pos\Models\Customer;
use Modules\Pos\Models\StoreCreditLedgerEntry;

/**
 * The in-person, staff-operated checkout path (POS Terminal). Distinct from
 * ProcessSyncBatchJob (which replays batches from OFFLINE devices) but
 * deliberately mirrors its invariants exactly: same invoice-number
 * sequence, same append-only stock_ledger semantics, same order/payment
 * status machine — so reporting and the read-models never need to know
 * which path a given order came from.
 *
 * Runs synchronously (not queued) because a cashier is standing at the
 * till waiting for the receipt — this is the one POS write path that must
 * complete within the request, and it does: a handful of small inserts in
 * a single DB transaction, no external calls except (for M-Pesa) a queued
 * STK push dispatched AFTER commit.
 *
 * @param  array<int, array{product_id: string, quantity: float, unit_price: float}>  $cartLines
 * @param  array<int, array{method: string, amount: float, payer_phone?: string}>  $paymentLines
 */
final class PosCheckoutService
{
    public function checkout(
        array $cartLines,
        array $paymentLines,
        ?string $customerId,
        string $cashierUserId,
        ?string $warehouseId = null,
    ): Order {
        $this->assertPaymentsCoverTotal($cartLines, $paymentLines);

        $order = DB::transaction(function () use ($cartLines, $paymentLines, $customerId, $cashierUserId, $warehouseId): Order {
            $order = Order::create([
                'id' => (string) Str::uuid7(),
                'local_reference' => 'TERM-'.now()->format('His').'-'.random_int(100, 999),
                'device_id' => null, // in-person terminal, not a registered offline device
                'customer_id' => $customerId,
                'cashier_user_id' => $cashierUserId,
                'warehouse_id' => $warehouseId,
                'currency' => 'KES',
                'order_date' => now(),
                'client_created_at' => now(),
                'client_updated_at' => now(),
                'version' => 1,
                'status' => 'awaiting_payment',
                'synced_at' => now(),
            ]);

            [$subtotal, $taxTotal, $discountTotal] = [0.0, 0.0, 0.0];

            foreach ($cartLines as $line) {
                $product = Product::findOrFail($line['product_id']);
                $quantity = (float) $line['quantity'];
                $basePrice = (float) $product->unit_price;
                $unitPrice = (float) $line['unit_price']; // pre-computed with any active promotion applied by the terminal UI
                $discountPerUnit = max(0, $basePrice - $unitPrice);

                $lineSubtotal = $quantity * $unitPrice;
                $taxAmount = round($lineSubtotal * (float) $product->tax_rate, 2);

                $item = OrderItem::create([
                    'id' => (string) Str::uuid7(),
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name_snapshot' => $product->name,
                    'product_sku_snapshot' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'tax_rate' => $product->tax_rate,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => round($discountPerUnit * $quantity, 2),
                    'line_subtotal' => $lineSubtotal,
                    'line_total' => $lineSubtotal + $taxAmount,
                    'client_created_at' => now(),
                    'client_updated_at' => now(),
                    'version' => 1,
                    'synced_at' => now(),
                ]);

                StockLedgerEntry::create([
                    'id' => (string) Str::uuid7(),
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,
                    'movement_type' => 'sale',
                    'quantity_delta' => -$quantity,
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                    'occurred_at' => now(),
                    'version' => 1,
                    'synced_at' => now(),
                ]);

                $subtotal += $lineSubtotal;
                $taxTotal += $taxAmount;
                $discountTotal += $discountPerUnit * $quantity;
            }

            $order->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => $discountTotal,
                'grand_total' => $subtotal + $taxTotal,
                // Same server-only sequence ProcessSyncBatchJob uses — a
                // terminal sale and a synced offline sale are indistinguishable
                // in the accounting ledger.
                'invoice_number' => sprintf('INV-%06d', DB::selectOne("SELECT nextval('pos_invoice_number_seq') AS n")->n),
                'status' => 'paid',
            ]);

            foreach ($paymentLines as $paymentLine) {
                $this->recordPayment($order, $paymentLine, $customerId);
            }

            return $order->fresh(['items', 'payments']);
        });

        // Dispatched AFTER the transaction commits (queue connections with
        // after_commit=true — see config/queue.php) so an STK push is never
        // fired for a sale that ultimately rolled back.
        foreach ($order->payments->where('method', 'mpesa')->where('status', 'pending') as $payment) {
            InitiateMpesaStkPushJob::dispatch($payment->id)->onQueue('mpesa');
        }

        return $order;
    }

    private function recordPayment(Order $order, array $line, ?string $customerId): Payment
    {
        if ($line['method'] === 'store_credit') {
            $customer = Customer::findOrFail($customerId);

            if ((float) $customer->store_credit_balance < (float) $line['amount']) {
                throw new \RuntimeException('Customer does not have enough store credit for this payment line.');
            }

            StoreCreditLedgerEntry::create([
                'id' => (string) Str::uuid7(),
                'customer_id' => $customer->id,
                'amount' => -$line['amount'],
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'note' => "Redeemed at checkout for order {$order->local_reference}",
            ]);

            $customer->decrement('store_credit_balance', $line['amount']);
        }

        return Payment::create([
            'id' => (string) Str::uuid7(),
            'order_id' => $order->id,
            'method' => $line['method'],
            'status' => $line['method'] === 'mpesa' ? 'pending' : 'completed',
            'amount' => $line['amount'],
            'currency' => 'KES',
            'payer_phone' => $line['payer_phone'] ?? null,
            'paid_at' => $line['method'] === 'mpesa' ? null : now(),
            'client_created_at' => now(),
            'client_updated_at' => now(),
            'version' => 1,
            'synced_at' => now(),
        ]);
    }

    private function assertPaymentsCoverTotal(array $cartLines, array $paymentLines): void
    {
        if (empty($cartLines)) {
            throw new \RuntimeException('Cannot check out an empty cart.');
        }

        // Mirrors exactly the subtotal+tax math the transaction below will
        // perform, so this pre-flight check can never diverge from what
        // actually gets charged (previously this ignored tax entirely).
        $productTaxRates = Product::query()
            ->whereIn('id', collect($cartLines)->pluck('product_id')->all())
            ->pluck('tax_rate', 'id');

        $cartTotal = collect($cartLines)->sum(function (array $line) use ($productTaxRates): float {
            $lineSubtotal = (float) $line['unit_price'] * (float) $line['quantity'];
            $taxRate = (float) ($productTaxRates[$line['product_id']] ?? 0);

            return round($lineSubtotal + round($lineSubtotal * $taxRate, 2), 2);
        });

        $paymentTotal = collect($paymentLines)->sum(fn (array $line): float => (float) $line['amount']);

        // Split payments (cash + M-Pesa + store credit in one transaction)
        // are the whole point — the only rule is they must sum to the
        // total, in whatever combination the cashier chooses.
        if (round($paymentTotal, 2) < round($cartTotal, 2)) {
            throw new \RuntimeException('Payment total does not cover the cart total.');
        }
    }
}

<?php

declare(strict_types=1);

namespace Modules\Pos\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Pos\Models\Order;
use Modules\Pos\Models\OrderItem;
use Modules\Pos\Models\Refund;
use Modules\Pos\Models\StockLedgerEntry;
use Modules\Pos\Models\StoreCreditLedgerEntry;

/**
 * Centralizes the "return / refund / store credit" workflow so it's
 * exercised identically whether triggered from the OrderResource's
 * ProcessRefundAction or, later, a return kiosk / API endpoint — no
 * money or stock logic lives in the Filament layer itself.
 */
final class RefundService
{
    /**
     * @throws \RuntimeException if the refund would exceed what remains
     *         refundable on the order/item
     */
    public function process(
        Order $order,
        float $amount,
        string $method,
        string $processedByUserId,
        ?OrderItem $orderItem = null,
        ?string $reason = null,
    ): Refund {
        $refundable = $orderItem !== null
            ? (float) $orderItem->line_total
            : (float) $order->grand_total - $order->totalRefunded();

        if ($amount <= 0 || $amount > $refundable) {
            throw new \RuntimeException("Refund amount exceeds the refundable balance of {$refundable}.");
        }

        return DB::transaction(function () use ($order, $amount, $method, $processedByUserId, $orderItem, $reason): Refund {
            $refund = Refund::create([
                'order_id' => $order->id,
                'order_item_id' => $orderItem?->id,
                'amount' => $amount,
                'method' => $method,
                'reason' => $reason,
                'processed_by_user_id' => $processedByUserId,
            ]);

            // Returned stock goes back on the shelf — append-only, positive
            // delta, exactly mirroring how the original sale appended a
            // negative one (see ProcessSyncBatchJob::syncOrderItem).
            if ($orderItem !== null) {
                StockLedgerEntry::create([
                    'id' => (string) Str::uuid7(),
                    'product_id' => $orderItem->product_id,
                    'warehouse_id' => $order->warehouse_id,
                    'movement_type' => 'return',
                    'quantity_delta' => $orderItem->quantity,
                    'reference_type' => Refund::class,
                    'reference_id' => $refund->id,
                    'occurred_at' => now(),
                    'version' => 1,
                    'synced_at' => now(),
                ]);
            }

            if ($method === 'store_credit' && $order->customer_id !== null) {
                StoreCreditLedgerEntry::create([
                    'id' => (string) Str::uuid7(),
                    'customer_id' => $order->customer_id,
                    'amount' => $amount,
                    'reference_type' => Refund::class,
                    'reference_id' => $refund->id,
                    'note' => "Store credit issued for order {$order->invoice_number}",
                ]);

                $order->customer()->increment('store_credit_balance', $amount);
            }

            $fullyRefunded = ($order->totalRefunded()) >= (float) $order->grand_total;
            $order->update(['status' => $fullyRefunded ? 'refunded' : 'partially_refunded']);

            return $refund;
        });
    }
}

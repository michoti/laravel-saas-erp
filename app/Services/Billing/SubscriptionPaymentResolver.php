<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\SubscriptionInvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Models\SubscriptionInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The central-billing counterpart to
 * Modules\Pos\Services\Mpesa\MpesaPaymentResolver — structurally
 * identical idempotency pattern (hash the result, guard against replay,
 * resolve exactly once), deliberately NOT the same class or the same
 * table. A subscription invoice payment and a POS sale payment must
 * never be resolvable through the same code path, because that path
 * would need read/write access to both a tenant's `payments` table and
 * the central `subscription_invoices` table — exactly the cross-boundary
 * access this design avoids entirely by having two small, near-identical,
 * but fully independent resolvers instead of one "generic" one.
 */
final class SubscriptionPaymentResolver
{
    public function resolve(
        string $checkoutRequestId,
        int $resultCode,
        ?string $resultDesc,
        ?string $mpesaReceipt,
    ): void {
        $hash = hash('sha256', $checkoutRequestId.'|'.$resultCode.'|'.$mpesaReceipt);

        DB::connection('central')->transaction(function () use ($checkoutRequestId, $resultCode, $resultDesc, $mpesaReceipt, $hash): void {
            $invoice = SubscriptionInvoice::where('checkout_request_id', $checkoutRequestId)
                ->lockForUpdate()
                ->first();

            if ($invoice === null) {
                Log::channel('mpesa_critical')->warning('billing.resolve.unknown_checkout_request', ['checkout_request_id' => $checkoutRequestId]);

                return;
            }

            $alreadyResolved = $invoice->status->isResolved()
                || SubscriptionInvoice::where('transaction_hash', $hash)->exists();

            if ($alreadyResolved) {
                return;
            }

            $invoice->update([
                'status' => $resultCode === 0 ? SubscriptionInvoiceStatus::Paid : SubscriptionInvoiceStatus::Failed,
                'transaction_hash' => $hash,
                'mpesa_receipt_number' => $mpesaReceipt,
                'paid_at' => $resultCode === 0 ? now() : null,
                'failure_reason' => $resultCode === 0 ? null : ($resultDesc ?? 'unknown'),
            ]);

            if ($resultCode === 0) {
                $subscription = $invoice->subscription()->lockForUpdate()->first();

                $subscription->update([
                    'status' => SubscriptionStatus::Active,
                    'current_period_start' => $subscription->current_period_end,
                    'current_period_end' => $subscription->current_period_end->addDays($subscription->plan->billing_interval_days),
                ]);
            }
        });
    }
}

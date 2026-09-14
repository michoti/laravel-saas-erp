<?php

declare(strict_types=1);

namespace Modules\Pos\Services\Mpesa;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Pos\Events\PaymentConfirmed;
use Modules\Pos\Models\Payment;

/**
 * The single, shared implementation of "what does it mean for an M-Pesa
 * payment to resolve to success or failure" — used identically by
 * MpesaCallbackController (the real-time path, when Safaricom's webhook
 * arrives) and ReconcilePendingMpesaPaymentsCommand (the fallback path,
 * when it doesn't). Having exactly one idempotent resolver means a
 * callback and a reconciliation check racing each other can never both
 * "win" and double-process the same payment.
 */
final class MpesaPaymentResolver
{
    public function resolve(
        string $checkoutRequestId,
        int $resultCode,
        ?string $resultDesc,
        ?string $mpesaReceipt,
    ): void {
        $hash = hash('sha256', $checkoutRequestId.'|'.$resultCode.'|'.$mpesaReceipt);

        DB::transaction(function () use ($checkoutRequestId, $resultCode, $resultDesc, $mpesaReceipt, $hash): void {
            $payment = Payment::where('checkout_request_id', $checkoutRequestId)
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                Log::warning('mpesa.resolve.unknown_checkout_request', ['checkout_request_id' => $checkoutRequestId]);

                return;
            }

            // --- Idempotency guard — safe no-op on replay from either path ---
            $alreadyResolved = in_array($payment->status, ['completed', 'failed'], true)
                || Payment::where('transaction_hash', $hash)->exists();

            if ($alreadyResolved) {
                return;
            }

            $payment->update([
                'status' => $resultCode === 0 ? 'completed' : 'failed',
                'transaction_hash' => $hash,
                'mpesa_receipt_number' => $mpesaReceipt,
                'paid_at' => $resultCode === 0 ? now() : null,
                'failure_reason' => $resultCode === 0 ? null : ($resultDesc ?? 'unknown'),
            ]);

            if ($resultCode === 0) {
                $payment->order()->update(['status' => 'paid']);
            }

            event(new PaymentConfirmed($payment->fresh()));
        });
    }
}

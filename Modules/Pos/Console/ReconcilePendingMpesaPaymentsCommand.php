<?php

declare(strict_types=1);

namespace Modules\Pos\Console;

use Illuminate\Console\Command;
use Modules\Pos\Models\Payment;
use Modules\Pos\Services\Mpesa\MpesaClient;
use Modules\Pos\Services\Mpesa\MpesaPaymentResolver;

/**
 * Automatic reconciliation for M-Pesa payments: Safaricom's callback is
 * at-least-once in the common case, but not guaranteed — a dropped
 * webhook, a customer who closes the STK prompt without deciding, or a
 * network partition on either end can leave a payment stuck in
 * "awaiting_confirmation" forever if nothing else checks on it.
 *
 * This command runs on a schedule (see routes/console.php) and actively
 * queries Daraja's STK Push Query endpoint for anything that's gone
 * quiet, resolving it through the exact same idempotent path a real
 * callback would use — so whichever arrives first (the webhook or this
 * sweep) wins cleanly, and the other is a safe no-op.
 */
final class ReconcilePendingMpesaPaymentsCommand extends Command
{
    protected $signature = 'pos:reconcile-mpesa-payments';

    protected $description = 'Actively query Daraja for any M-Pesa payment stuck awaiting a callback, and resolve or time it out.';

    public function handle(MpesaClient $mpesa, MpesaPaymentResolver $resolver): int
    {
        $staleAfter = now()->subMinutes((int) config('mpesa.reconciliation.stale_after_minutes'));
        $giveUpAfter = now()->subMinutes((int) config('mpesa.reconciliation.give_up_after_minutes'));

        $pending = Payment::query()
            ->where('method', 'mpesa')
            ->where('status', 'awaiting_confirmation')
            ->whereNotNull('checkout_request_id')
            ->whereNotNull('stk_pushed_at')
            ->where('stk_pushed_at', '<=', $staleAfter)
            ->get();

        foreach ($pending as $payment) {
            // Given up entirely: stop polling Daraja for this one and let
            // staff know it needs a manual look (e.g. the customer never
            // completed the prompt at all).
            if ($payment->stk_pushed_at->lte($giveUpAfter)) {
                $resolver->resolve(
                    checkoutRequestId: $payment->checkout_request_id,
                    resultCode: 1,
                    resultDesc: 'Reconciliation timed out waiting for a Daraja result',
                    mpesaReceipt: null,
                );
                $this->warn("Timed out: {$payment->id}");

                continue;
            }

            try {
                $result = $mpesa->stkPushQuery($payment->checkout_request_id);
            } catch (\Throwable $e) {
                $this->error("Query failed for {$payment->id}: {$e->getMessage()}");

                continue; // try again on the next scheduled run
            }

            // Daraja returns ResultCode only once the customer has actually
            // responded to the prompt (approved or cancelled) — 1032/1037
            // and similar mean "still waiting", which we simply leave
            // pending for the next sweep.
            if (! isset($result['ResultCode'])) {
                continue;
            }

            $resolver->resolve(
                checkoutRequestId: $payment->checkout_request_id,
                resultCode: (int) $result['ResultCode'],
                resultDesc: $result['ResultDesc'] ?? null,
                mpesaReceipt: null, // the query endpoint doesn't return a receipt number; the callback (if it still arrives) will fill it in — resolve() is a no-op by then either way
            );

            $this->info("Reconciled {$payment->id}: ResultCode {$result['ResultCode']}");
        }

        return self::SUCCESS;
    }
}

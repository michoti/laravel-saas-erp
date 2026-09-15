<?php

declare(strict_types=1);

namespace App\Jobs\Billing;

use App\Enums\SubscriptionInvoiceStatus;
use App\Models\SubscriptionInvoice;
use App\Services\Billing\SubscriptionMpesaGateway;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * The billing-side counterpart to Modules\Pos\Jobs\InitiateMpesaStkPushJob
 * — same shape, same idempotency guard (skip if the invoice has already
 * progressed past `pending`), but running on the dedicated `billing`
 * queue rather than `mpesa`, so a burst of subscription renewals never
 * competes with tenant POS sales for STK Push throughput.
 */
final class InitiateSubscriptionStkPushJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 90];

    public function __construct(
        public readonly int $invoiceId,
        public readonly string $payerPhone,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->invoiceId;
    }

    public function handle(SubscriptionMpesaGateway $mpesa): void
    {
        $invoice = SubscriptionInvoice::with('subscription.tenant')->findOrFail($this->invoiceId);

        if ($invoice->status !== SubscriptionInvoiceStatus::Pending) {
            return; // already progressed (e.g. reprocessed job) — no-op
        }

        $response = $mpesa->stkPush(
            phone: $this->payerPhone,
            amount: (int) round((float) $invoice->amount),
            accountReference: $invoice->subscription->tenant->name,
            transactionDesc: "Subscription renewal #{$invoice->id}",
            callbackUrl: route('billing.mpesa.callback'),
        );

        $invoice->update([
            'checkout_request_id' => $response['CheckoutRequestID'],
            'merchant_request_id' => $response['MerchantRequestID'] ?? null,
            'status' => SubscriptionInvoiceStatus::AwaitingConfirmation,
            'payer_phone' => $this->payerPhone,
            'stk_pushed_at' => now(),
        ]);
    }
}

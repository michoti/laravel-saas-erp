<?php

declare(strict_types=1);

namespace Modules\Pos\Jobs;

use App\Enums\PaymentStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Pos\App\Models\Payment;
use Modules\Pos\Facades\Mpesa;

final class InitiateMpesaStkPushJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 90];

    public function __construct(public readonly string $paymentId) {}

    public function uniqueId(): string
    {
        return $this->paymentId;
    }

    public function handle(): void
    {
        $payment = Payment::with('order')->findOrFail($this->paymentId);

        if ($payment->status !== PaymentStatus::Pending) {
            return; // already progressed (e.g. reprocessed job) — no-op
        }

        $response = Mpesa::stkPush(
            phone: $payment->payer_phone,
            amount: (int) round((float) $payment->amount),
            accountReference: $payment->order->invoice_number ?? $payment->order_id,
            transactionDesc: "POS order {$payment->order_id}",
            callbackUrl: route('mpesa.callback', ['tenant' => tenant('id')]),
        );

        $payment->update([
            'checkout_request_id' => $response['CheckoutRequestID'],
            'merchant_request_id' => $response['MerchantRequestID'] ?? null,
            'status' => 'awaiting_confirmation',
            'stk_pushed_at' => now(),
        ]);
    }
}

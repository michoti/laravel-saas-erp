<?php

declare(strict_types=1);

namespace App\Services\Mpesa;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * A minimal Daraja (M-Pesa) API client built directly on Laravel's HTTP
 * client — no M-Pesa package is a dependency of this project. The public
 * shape (stkPush(), a facade-friendly design, credential-driven config)
 * deliberately mirrors iankumu/mpesa's ergonomics as a design reference —
 * see https://github.com/Iankumu/mpesa — without being installed as one.
 *
 * THIS IS THE ONE PLACE Daraja's HTTP contract is implemented anywhere in
 * the codebase. Both the POS module's tenant sale payments
 * (Modules\Pos\Services\Mpesa\PosMpesaGateway) and central subscription
 * billing (App\Services\Billing\SubscriptionMpesaGateway) call into this
 * same class — never a second, parallel STK Push implementation — so a
 * Daraja API fix or behavior change only ever needs to happen once.
 *
 * It is instantiated per MpesaCredentials rather than as a single global
 * singleton, because the whole point is that tenant POS credentials and
 * platform billing credentials must never be the same instance, the same
 * cached token, or discoverable from one another — see MpesaCredentials's
 * own docblock for why.
 */
final class MpesaClient
{
    public function __construct(private readonly MpesaCredentials $credentials) {}

    /**
     * Lipa Na M-Pesa Online (STK Push). Prompts the customer's phone for
     * their M-Pesa PIN and returns immediately with a CheckoutRequestID —
     * the actual payment result arrives later via the callback URL, or,
     * as a fallback, via a reconciliation sweep querying stkPushQuery().
     *
     * @return array{MerchantRequestID: string, CheckoutRequestID: string, ResponseCode: string, ResponseDescription: string, CustomerMessage: string}
     */
    public function stkPush(
        string $phone,
        int $amount,
        string $accountReference,
        string $transactionDesc,
        string $callbackUrl,
    ): array {
        $timestamp = now()->format('YmdHis');
        $password = base64_encode($this->credentials->shortcode.$this->credentials->passkey.$timestamp);

        $transactionType = $this->credentials->transactionType === 'till'
            ? 'CustomerBuyGoodsOnline'
            : 'CustomerPayBillOnline';

        $response = $this->http()->post('/mpesa/stkpush/v1/processrequest', [
            'BusinessShortCode' => $this->credentials->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => $transactionType,
            'Amount' => $amount,
            'PartyA' => $this->normalizePhone($phone),
            'PartyB' => $this->credentials->shortcode,
            'PhoneNumber' => $this->normalizePhone($phone),
            'CallBackURL' => $callbackUrl,
            'AccountReference' => substr($accountReference, 0, 12),
            'TransactionDesc' => substr($transactionDesc, 0, 13),
        ]);

        if ($response->failed() || ($response->json('ResponseCode') !== '0' && $response->json('errorCode') !== null)) {
            Log::channel('mpesa_critical')->error('mpesa.stk_push.failed', [
                'shortcode' => $this->credentials->shortcode,
                'phone' => $this->normalizePhone($phone),
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new RuntimeException('M-Pesa STK push request was rejected: '.($response->json('errorMessage') ?? $response->body()));
        }

        return $response->json();
    }

    /**
     * STK Push Query — asks Daraja directly "what happened to this
     * CheckoutRequestID?" Used by any reconciliation sweep when a
     * callback never arrives, for either POS sales or subscription
     * invoices, so a payment doesn't stay pending forever.
     *
     * @return array{ResultCode?: string, ResultDesc?: string}
     */
    public function stkPushQuery(string $checkoutRequestId): array
    {
        $timestamp = now()->format('YmdHis');
        $password = base64_encode($this->credentials->shortcode.$this->credentials->passkey.$timestamp);

        $response = $this->http()->post('/mpesa/stkpushquery/v1/query', [
            'BusinessShortCode' => $this->credentials->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ]);

        return $response->json() ?? [];
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withToken($this->accessToken())
            ->acceptJson()
            ->timeout(15);
    }

    private function baseUrl(): string
    {
        return $this->credentials->env === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    /**
     * OAuth tokens are valid for roughly an hour; cached PER CREDENTIAL
     * SET (see MpesaCredentials::cacheKey()) so a tenant's token and the
     * platform's token — or two different tenants' tokens — are never
     * confused with one another, and a burst of checkouts doesn't request
     * a fresh token per sale.
     */
    private function accessToken(): string
    {
        return Cache::remember(
            'mpesa:access_token:'.$this->credentials->cacheKey(),
            (int) config('mpesa.access_token_ttl_seconds', 3500),
            function (): string {
                $response = Http::baseUrl($this->baseUrl())
                    ->withBasicAuth($this->credentials->consumerKey, $this->credentials->consumerSecret)
                    ->timeout(15)
                    ->get('/oauth/v1/generate', ['grant_type' => 'client_credentials']);

                if ($response->failed()) {
                    Log::channel('mpesa_critical')->error('mpesa.access_token.failed', [
                        'shortcode' => $this->credentials->shortcode,
                        'status' => $response->status(),
                    ]);

                    throw new RuntimeException('Unable to obtain an M-Pesa access token: '.$response->body());
                }

                return (string) $response->json('access_token');
            }
        );
    }

    /** Normalizes any common Kenyan phone format to Safaricom's required 2547XXXXXXXX / 2541XXXXXXXX. */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        return match (true) {
            str_starts_with($digits, '254') => $digits,
            str_starts_with($digits, '0') => '254'.substr($digits, 1),
            str_starts_with($digits, '7'), str_starts_with($digits, '1') => '254'.$digits,
            default => $digits,
        };
    }
}

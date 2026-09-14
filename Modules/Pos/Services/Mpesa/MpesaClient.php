<?php

declare(strict_types=1);

namespace Modules\Pos\Services\Mpesa;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * A minimal Daraja (M-Pesa) API client built directly on Laravel's HTTP
 * client, rather than a third-party package — no M-Pesa package appears
 * in this project's composer.json. The public API (stkPush(), a facade
 * accessed as `Mpesa::stkPush(...)`, a config/mpesa.php config file with
 * env/consumer_key/consumer_secret/shortcode/passkey) deliberately mirrors
 * iankumu/mpesa's shape, since that package's design is a clean, proven
 * reference for how a Laravel-idiomatic Daraja wrapper should feel — see
 * https://github.com/Iankumu/mpesa.
 */
final class MpesaClient
{
    public function __construct(private readonly ?string $env = null) {}

    /**
     * Lipa Na M-Pesa Online (STK Push). Prompts the customer's phone for
     * their M-Pesa PIN and returns immediately with a CheckoutRequestID —
     * the actual payment result arrives later via the callback URL (see
     * MpesaCallbackController) or, as a fallback, via
     * ReconcilePendingMpesaPaymentsCommand's periodic query.
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
        $shortcode = (string) config('mpesa.shortcode');
        $password = base64_encode($shortcode.config('mpesa.passkey').$timestamp);

        $transactionType = config('mpesa.transaction_type') === 'till'
            ? 'CustomerBuyGoodsOnline'
            : 'CustomerPayBillOnline';

        $response = $this->http()->post('/mpesa/stkpush/v1/processrequest', [
            'BusinessShortCode' => $shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => $transactionType,
            'Amount' => $amount,
            'PartyA' => $this->normalizePhone($phone),
            'PartyB' => $shortcode,
            'PhoneNumber' => $this->normalizePhone($phone),
            'CallBackURL' => $callbackUrl,
            'AccountReference' => substr($accountReference, 0, 12),
            'TransactionDesc' => substr($transactionDesc, 0, 13),
        ]);

        if ($response->failed() || ($response->json('ResponseCode') !== '0' && $response->json('errorCode') !== null)) {
            Log::channel('pos_critical')->error('mpesa.stk_push.failed', [
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
     * CheckoutRequestID?" Used by ReconcilePendingMpesaPaymentsCommand
     * when the callback never arrives, so a payment doesn't stay
     * "awaiting_confirmation" forever.
     *
     * @return array{ResultCode: string, ResultDesc: string}
     */
    public function stkPushQuery(string $checkoutRequestId): array
    {
        $timestamp = now()->format('YmdHis');
        $shortcode = (string) config('mpesa.shortcode');
        $password = base64_encode($shortcode.config('mpesa.passkey').$timestamp);

        $response = $this->http()->post('/mpesa/stkpushquery/v1/query', [
            'BusinessShortCode' => $shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ]);

        return $response->json() ?? [];
    }

    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withToken($this->accessToken())
            ->acceptJson()
            ->timeout(15);
    }

    private function baseUrl(): string
    {
        return ($this->env ?? config('mpesa.env')) === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    /**
     * OAuth tokens are valid for roughly an hour; cached so a burst of
     * checkouts doesn't request a fresh token per sale.
     */
    private function accessToken(): string
    {
        return Cache::remember('mpesa:access_token:'.($this->env ?? config('mpesa.env')), config('mpesa.access_token_ttl_seconds'), function (): string {
            $response = Http::baseUrl($this->baseUrl())
                ->withBasicAuth((string) config('mpesa.consumer_key'), (string) config('mpesa.consumer_secret'))
                ->timeout(15)
                ->get('/oauth/v1/generate', ['grant_type' => 'client_credentials']);

            if ($response->failed()) {
                throw new RuntimeException('Unable to obtain an M-Pesa access token: '.$response->body());
            }

            return (string) $response->json('access_token');
        });
    }

    /** Normalizes any common Kenyan phone format to Safaricom's required 2547XXXXXXXX / 2541XXXXXXXX. */
    private function normalizePhone(string $phone): string
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

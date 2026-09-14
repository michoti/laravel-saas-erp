<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Services\Mpesa\MpesaClient;
use App\Services\Mpesa\MpesaCredentials;

/**
 * The billing-side counterpart to Modules\Pos\Services\Mpesa\PosMpesaGateway
 * — same pattern (a thin wrapper resolving the right MpesaCredentials and
 * delegating to the one shared MpesaClient), but resolving the PLATFORM's
 * own credentials from config/mpesa.php's `billing` block instead of a
 * tenant's TenantMpesaSetting row. Neither gateway can reach the other's
 * credentials or transaction records — see MpesaCredentials's docblock —
 * which is what "privacy... between tenants and central db" means
 * concretely in this codebase: two parallel, non-overlapping call paths
 * sharing only the stateless Daraja HTTP implementation, never any data.
 *
 * Bound as a singleton (see AppServiceProvider) — unlike PosMpesaGateway,
 * platform billing credentials don't change per request.
 */
final class SubscriptionMpesaGateway
{
    private readonly MpesaClient $client;

    public function __construct()
    {
        $this->client = new MpesaClient(MpesaCredentials::fromConfig('mpesa.billing'));
    }

    public function stkPush(string $phone, int $amount, string $accountReference, string $transactionDesc, string $callbackUrl): array
    {
        return $this->client->stkPush($phone, $amount, $accountReference, $transactionDesc, $callbackUrl);
    }

    public function stkPushQuery(string $checkoutRequestId): array
    {
        return $this->client->stkPushQuery($checkoutRequestId);
    }
}

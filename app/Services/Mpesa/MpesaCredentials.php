<?php

declare(strict_types=1);

namespace App\Services\Mpesa;

/**
 * An explicit, self-contained set of Daraja credentials. MpesaClient takes
 * one of these per call rather than reading `config('mpesa.*')` itself —
 * this is what makes the SAME client class safely reusable for both:
 *
 *   - Platform subscription billing (App\Services\Billing\*), using the
 *     platform's OWN Daraja app, configured centrally via config/mpesa.php.
 *   - Tenant POS sales (Modules\Pos\*), using EACH TENANT'S OWN Daraja
 *     app/Till, stored encrypted in that tenant's own database
 *     (see Modules\Pos\Models\TenantMpesaSetting) and never visible to,
 *     or queryable from, the central database or another tenant.
 *
 * A tenant's PIN/secret never has to pass through, or be readable by, any
 * code path that also touches platform billing credentials, and vice
 * versa — the two are simply different instances of this same value
 * object, constructed from two different, access-isolated sources.
 */
final readonly class MpesaCredentials
{
    public function __construct(
        public string $consumerKey,
        public string $consumerSecret,
        public string $shortcode,
        public string $passkey,
        public string $env = 'sandbox',
        public string $transactionType = 'paybill',
    ) {}

    public static function fromConfig(string $configKey): self
    {
        return new self(
            consumerKey: (string) config("{$configKey}.consumer_key"),
            consumerSecret: (string) config("{$configKey}.consumer_secret"),
            shortcode: (string) config("{$configKey}.shortcode"),
            passkey: (string) config("{$configKey}.passkey"),
            env: (string) config("{$configKey}.env", 'sandbox'),
            transactionType: (string) config("{$configKey}.transaction_type", 'paybill'),
        );
    }

    /**
     * A stable, non-secret identifier for this credential set — used only
     * to namespace the OAuth token cache (see MpesaClient::accessToken())
     * so two different Daraja apps never share a cached token. Never log
     * or expose consumerKey/consumerSecret/passkey themselves.
     */
    public function cacheKey(): string
    {
        return hash('sha256', $this->shortcode.'|'.$this->env);
    }
}

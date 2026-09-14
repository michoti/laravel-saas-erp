<?php

declare(strict_types=1);

namespace Modules\Pos\Models;

use App\Services\Mpesa\MpesaCredentials;
use Illuminate\Database\Eloquent\Model;

/**
 * A tenant's OWN Daraja (M-Pesa) credentials — one row per tenant
 * database (effectively a singleton within each tenant). Deliberately
 * separate from the platform's own billing credentials
 * (config/mpesa.php), and physically isolated in the tenant's own
 * database rather than a shared central table — see the migration's
 * docblock for why that isolation is the point, not an implementation
 * detail.
 */
final class TenantMpesaSetting extends Model
{
    protected $table = 'tenant_mpesa_settings';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            // Laravel's `encrypted` cast transparently encrypts on write
            // and decrypts on read using APP_KEY — the raw secret is
            // never stored in plaintext even within the tenant's own
            // database.
            'consumer_key' => 'encrypted',
            'consumer_secret' => 'encrypted',
            'shortcode' => 'encrypted',
            'passkey' => 'encrypted',
            'verified_at' => 'datetime',
        ];
    }

    public static function current(): ?self
    {
        return static::query()->first();
    }

    public function isConfigured(): bool
    {
        return filled($this->consumer_key)
            && filled($this->consumer_secret)
            && filled($this->shortcode)
            && filled($this->passkey);
    }

    public function toCredentials(): MpesaCredentials
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('This tenant has not configured M-Pesa credentials yet.');
        }

        return new MpesaCredentials(
            consumerKey: $this->consumer_key,
            consumerSecret: $this->consumer_secret,
            shortcode: $this->shortcode,
            passkey: $this->passkey,
            env: $this->env,
            transactionType: $this->transaction_type,
        );
    }
}

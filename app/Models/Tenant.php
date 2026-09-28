<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasSubscription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * Lives in the CENTRAL database only (the base model already applies
 * stancl's CentralConnection trait). Owns billing (HasSubscription +
 * App\Services\Billing\SubscriptionService), the module activation registry
 * and the tenant's Filament theme. Business data lives in the separate
 * `tenant_<id>` database.
 */
final class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;
    use HasFactory;
    use HasSubscription;

    /**
     * Real columns on the `tenants` table. Anything NOT listed here is
     * moved by stancl's VirtualColumn into the `data` JSON column.
     *
     * @return list<string>
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'billing_phone',
            'theme', // jsonb: { primary_color, logo_url, font_family, custom_css }
            'created_at',
            'updated_at',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'theme' => 'array',
        ]);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(TenantModule::class);
    }

    /**
     * One domain per tenant (the oldest), safe to eager load for lists.
     */
    public function primaryDomain(): HasOne
    {
        return $this->hasOne(config('tenancy.domain_model'))->oldestOfMany();
    }

    /**
     * Enabled module keys, memoized per model instance (one tiny indexed
     * query per request). Deliberately not stored in the shared cache:
     * with CacheTenancyBootstrapper active, a key written while tenancy is
     * initialized cannot be busted from the central admin panel.
     *
     * @return list<string>
     */
    public function enabledModuleKeys(): array
    {
        return once(fn (): array => $this->modules()
            ->whereNotNull('enabled_at')
            ->pluck('module_key')
            ->all());
    }

    public function hasModuleEnabled(string $moduleKey): bool
    {
        return in_array($moduleKey, $this->enabledModuleKeys(), true);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasSubscription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * Lives in the CENTRAL database only. Owns billing (via HasSubscription +
 * App\Services\Billing\SubscriptionService — no Laravel Cashier anywhere
 * in this codebase), the module activation registry, and the tenant's
 * Filament theme customization. The tenant's actual business data (POS,
 * CRM, Invoicing...) lives in a fully separate `tenant_<uuid>` Postgres
 * database — see config/tenancy.php.
 */
final class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;
    use HasFactory;
    use HasSubscription;

    protected $connection = 'central';

    public static function getCustomColumns(): array
    {
        return [
            ...parent::getCustomColumns(),
            'name',
            'billing_phone',
            'theme',
        ];
    }

    protected function casts(): array
    {
        return [
            'theme' => 'array', // { primary_color, logo_url, font_family, custom_css }
        ];
    }

    public function modules(): HasMany
    {
        return $this->hasMany(TenantModule::class);
    }

    public function hasModuleEnabled(string $moduleKey): bool
    {
        $enabledModules = Cache::remember(
            "tenant:{$this->id}:modules",
            now()->addMinutes(15),
            fn () => $this->modules()->whereNotNull('enabled_at')->pluck('module_key')->all()
        );

        return in_array($moduleKey, $enabledModules, true);
    }
}

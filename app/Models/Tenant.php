<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Cashier\Billable;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * Lives in the CENTRAL database only. Owns billing (Cashier), the module
 * activation registry, and the tenant's Filament theme customization.
 * The tenant's actual business data (POS, CRM, Invoicing...) lives in a
 * fully separate `tenant_<uuid>` Postgres database — see config/tenancy.php.
 */
final class Tenant extends BaseTenant implements TenantWithDatabase
{
    use Billable;
    use HasDatabase;
    use HasDomains;

    protected function casts(): array
    {
        return [
            'theme' => 'array', // { primary_color, logo_url, font_family, custom_css }
            'trial_ends_at' => 'datetime',
        ];
    }

    public function modules(): HasMany
    {
        return $this->hasMany(TenantModule::class);
    }

    public function hasModuleEnabled(string $moduleKey): bool
    {
        return $this->modules()->where('module_key', $moduleKey)->whereNotNull('enabled_at')->exists();
    }
}

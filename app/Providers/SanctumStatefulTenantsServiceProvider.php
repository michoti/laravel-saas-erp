<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;


/**
 * Keeps Sanctum's stateful domain list in sync with actual tenant
 * subdomains, so cookie-based SPA auth against `/api/user` (see routes/
 * api.php) works from any tenant subdomain, not just the central app URL
 * that config/sanctum.php's default computes.
 *
 * Runs at application boot, BEFORE any tenancy-identification middleware
 * executes, so `config('tenancy.domain_model')::query()` here always runs
 * against the central connection (the process-wide default at this point)
 * regardless of which domain the current request is for.
 *
 * Register in bootstrap/providers.php:
 *   App\Providers\SanctumStatefulTenantsServiceProvider::class,
 */
final class SanctumStatefulTenantsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Avoid querying domains during migrate / before the table exists
        if (! Schema::connection('central')->hasTable('domains')) {
            return;
        } 

        if (! class_exists(\Laravel\Sanctum\Sanctum::class)) {
            return;
        }

        $domainModel = config('tenancy.domain_model');

        if (! is_string($domainModel) || ! class_exists($domainModel)) {
            return;
        }

        $tenantDomains = Cache::remember(
            'sanctum:tenant-stateful-domains',
            now()->addMinutes(15),
            fn (): array => $domainModel::query()->pluck('domain')->all(),
        );

        config([
            'sanctum.stateful' => array_values(array_unique([
                ...config('sanctum.stateful', []),
                ...$tenantDomains,
            ])),
        ]);
    }
}

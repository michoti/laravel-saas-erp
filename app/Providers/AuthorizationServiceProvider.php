<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Central place for cross-cutting authorization defaults, kept separate
 * from AppServiceProvider so this repo's specific policy isn't buried in a
 * file that also does unrelated bootstrapping.
 *
 * Grants the tenant "Owner" role (seeded per-tenant by TenantDatabaseSeeder,
 * via spatie/laravel-permission) an implicit pass on every ability check —
 * the standard "super-admin" pattern spatie's own docs recommend, expressed
 * as a Gate::before hook rather than assigning every individual permission
 * to the role. Safe across guards: `method_exists($user, 'hasRole')` means
 * this silently no-ops for `PlatformAdminUser` (the `platform_admin` guard),
 * which has no roles/permissions concept of its own.
 *
 * This does not, by itself, add any fine-grained permission checks —
 * Manager/Cashier/Accountant still fall through to whatever explicit
 * permissions/policies you define per Resource/action. If those don't
 * exist yet for the POS module's Filament resources, add Policy classes
 * (or spatie/laravel-permission's `can:` checks) there; this provider only
 * establishes the Owner bypass.
 */
final class AuthorizationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::before(function ($user, string $ability): ?bool {
            if (method_exists($user, 'hasRole') && $user->hasRole('Owner')) {
                return true;
            }

            return null;
        });
    }
}

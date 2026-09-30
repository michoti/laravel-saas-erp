<?php

declare(strict_types=1);

namespace App\Actions\Tenants;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * Creates the first login for a freshly-provisioned tenant: a user in that
 * TENANT's own `users` table (not the central database), with the "Owner"
 * spatie/laravel-permission role and a random temporary password.
 *
 * MUST run after the tenant's database has been created AND migrated
 * (CreateDatabase + MigrateDatabase). If those run on a real queue instead
 * of synchronously, calling this immediately after Tenant::create() will
 * fail with "database does not exist" — see the retry command
 * `tenants:provision-owner`, which exists for exactly that case.
 *
 * ASSUMPTIONS: `App\Models\User` casts `password` as `hashed` (so a plain
 * string here is safe to store), and a `permission.php` config with guard
 * `web` is present inside the tenant database (per the ported
 * spatie/laravel-permission migration).
 */
final class ProvisionTenantOwnerUser
{
    /**
     * @return array{email: string, password: string}
     */
    public function handle(Tenant $tenant, string $ownerEmail): array
    {
        $password = Str::password(16);

        tenancy()->initialize($tenant);

        try {
            // Idempotency guard: never silently overwrite an existing
            // tenant's user base if this is called a second time (e.g. via
            // the manual retry command after a partial earlier failure).
            if (User::query()->exists()) {
                throw new RuntimeException("Tenant [{$tenant->getKey()}] already has users — owner not (re)created.");
            }

            $role = Role::findOrCreate('Owner', 'web');

            $owner = User::create([
                'id' => (string) Str::uuid(),
                'name' => 'Store Owner',
                'email' => $ownerEmail,
                'password' => $password,
                'email_verified_at' => now(),
            ]);

            $owner->assignRole($role);
        } catch (Throwable $e) {
            tenancy()->end();

            throw $e;
        }

        tenancy()->end();

        return ['email' => $ownerEmail, 'password' => $password];
    }
}

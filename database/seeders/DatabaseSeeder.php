<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Central-database seeder (`php artisan db:seed`). Tenant-database seeding
 * is a SEPARATE step — see TenantDatabaseSeeder, run automatically for
 * every tenant via `php artisan tenants:seed` (per the `seeder_parameters`
 * in config/tenancy.php).
 */
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlatformAdminSeeder::class,
            PlanSeeder::class,
            TenantSeeder::class,
        ]);
    }
}

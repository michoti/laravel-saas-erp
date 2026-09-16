<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Tenant;
use App\Services\Billing\SubscriptionService;
use Illuminate\Database\Seeder;

/**
 * Creates demo tenants in the CENTRAL database. Each Tenant::create() fires
 * TenantCreated -> CreateDatabase -> MigrateDatabase (see
 * TenancyServiceProvider), so the tenant's own database, migrations, and
 * (if you also run `php artisan tenants:seed`) TenantDatabaseSeeder all
 * follow automatically — this seeder only needs to describe the tenant
 * itself, which domain/modules it has, and which plan it's subscribed to.
 */
final class TenantSeeder extends Seeder
{
    public function run(SubscriptionService $subscriptions): void
    {
        $growthPlan = Plan::where('slug', 'growth')->firstOrFail();

        $tenant = Tenant::factory()->withPosEnabled()->create([
            'id' => 'demo-retail-co',
            'name' => 'Demo Retail Co',
            'billing_phone' => '254712345678',
            'theme' => [
                'primary_color' => '#2563EB',
                'font_family' => 'Inter',
                'logo_url' => null,
            ],
        ]);

        $tenant->domains()->create(['domain' => 'demo.laravelsaas.test']);
        $subscriptions->subscribe($tenant, $growthPlan);

        $this->command?->info("Demo tenant created: {$tenant->id} (domain: demo.laravelsaas.test)");
        $this->command?->info('Run `php artisan tenants:seed` to populate its database with demo staff/products/orders.');
    }
}

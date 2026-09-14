<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

final class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Starter', 'slug' => 'starter', 'price' => 2500, 'trial_days' => 14, 'sort_order' => 1, 'module_keys' => ['pos']],
            ['name' => 'Growth', 'slug' => 'growth', 'price' => 6000, 'trial_days' => 14, 'sort_order' => 2, 'module_keys' => ['pos', 'inventory']],
            ['name' => 'Scale', 'slug' => 'scale', 'price' => 15000, 'trial_days' => 7, 'sort_order' => 3, 'module_keys' => ['pos', 'inventory', 'invoicing', 'crm']],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan + ['currency' => 'KES', 'billing_interval_days' => 30, 'is_active' => true]
            );
        }
    }
}

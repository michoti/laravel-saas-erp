<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Tenant> */
final class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'id' => (string) Str::uuid(), // stancl's own id_generator would also fill this; set explicitly so factory-created tenants are deterministic in tests
            'name' => $name,
            'billing_phone' => '2547'.fake()->numberBetween(10000000, 99999999),
            'theme' => [
                'primary_color' => fake()->hexColor(),
                'font_family' => 'Inter',
            ],
            'data' => [],
        ];
    }

    /** A tenant with a domain + the POS module enabled — the common case for tests/demos. */
    public function withPosEnabled(): self
    {
        return $this->afterCreating(function (Tenant $tenant): void {
            $tenant->modules()->create(['module_key' => 'pos', 'enabled_at' => now()]);
        });
    }

    /** A tenant with an active subscription to the given (or first available) plan. */
    public function withSubscription(?\App\Models\Plan $plan = null): self
    {
        return $this->afterCreating(function (Tenant $tenant) use ($plan): void {
            app(\App\Services\Billing\SubscriptionService::class)
                ->subscribe($tenant, $plan ?? \App\Models\Plan::query()->firstOrFail());
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Tenants;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\Billing\SubscriptionService;
use DomainException;

/**
 * Single place where a superadmin picks a tenant's plan. Delegates the
 * actual period/status bookkeeping to SubscriptionService — the same
 * service TenantSeeder and the self-serve signup flow use — instead of
 * duplicating that logic here.
 *
 * ASSUMPTION: SubscriptionService exposes `subscribe(Tenant, Plan): Subscription`
 * for a tenant with no subscription yet, and `swap(Tenant, Plan): Subscription`
 * for changing an existing one (referenced by name in the subscriptions
 * migration's comment). Adjust the two calls below if the real signatures
 * differ.
 */
final class AssignTenantPlan
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function handle(Tenant $tenant, int|string $planId): Subscription
    {
        $plan = Plan::query()->findOrFail($planId);
        $existing = $tenant->subscription;

        // Defense in depth: the Select on the form already restricts
        // choices to active plans (plus the tenant's current one), but a
        // tampered Livewire payload could still submit a retired plan id.
        if (! $plan->is_active && $existing?->plan_id !== $plan->getKey()) {
            throw new DomainException("Plan \"{$plan->name}\" is not active and cannot be assigned to a tenant.");
        }

        $subscription = $existing
            ? $this->subscriptions->swap($tenant, $plan)
            : $this->subscriptions->subscribe($tenant, $plan);

        $tenant->unsetRelation('subscription');

        return $subscription;
    }
}

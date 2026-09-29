<?php

declare(strict_types=1);

namespace App\Actions\Tenants;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Tenant;

/**
 * Single place where a superadmin picks a tenant's plan.
 *
 * NEW subscription: starts now and follows the plan's own terms, like a
 * self-serve signup would. With `trial_days > 0` it is `trialing`, the
 * trial ends after that many days and the first period ends with the trial;
 * with no trial it is `active` for one `billing_interval_days` period.
 *
 * EXISTING subscription: only `plan_id` is swapped. Period dates, status
 * and invoices are left alone so a superadmin edit never silently resets a
 * billing cycle. If SubscriptionService::swap() handles proration or
 * invoicing, call it from the `exists` branch instead.
 */
final class AssignTenantPlan
{
    public function handle(Tenant $tenant, int|string $planId): void
    {
        $plan = Plan::query()->findOrFail($planId);
        $subscription = $tenant->subscription()->firstOrNew([]);

        if (! $subscription->exists) {
            $now = now();

            if ($plan->trial_days > 0) {
                $trialEnd = $now->copy()->addDays($plan->trial_days);

                $subscription->status = SubscriptionStatus::Trialing;
                $subscription->trial_ends_at = $trialEnd;
                $subscription->current_period_end = $trialEnd;
            } else {
                $subscription->status = SubscriptionStatus::Active;
                $subscription->current_period_end = $now->copy()->addDays($plan->billing_interval_days);
            }

            $subscription->current_period_start = $now;
        }

        $subscription->plan_id = $plan->getKey();
        $subscription->save();

        $tenant->unsetRelation('subscription');
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Tenants;

use App\Models\Tenant;

/**
 * Single place where a superadmin picks a tenant's plan.
 *
 * ASSUMPTIONS (adjust here, nowhere else): `Tenant::subscription()` is a
 * hasOne relation (from HasSubscription) and `plan_id` is fillable on the
 * subscription model. If the subscriptions table has other NOT NULL columns
 * without defaults (status, period dates...), add them to the second array.
 */
final class AssignTenantPlan
{
    public function handle(Tenant $tenant, int|string $planId): void
    {
        $tenant->subscription()->updateOrCreate([], [
            'plan_id' => $planId,
        ]);

        $tenant->unsetRelation('subscription');
    }
}

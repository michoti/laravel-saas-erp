<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\SubscriptionInvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Jobs\Billing\InitiateSubscriptionStkPushJob;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * The whole surface a tenant's billing lifecycle needs, shaped
 * deliberately after Laravel Cashier's Billable API
 * (subscribe/cancel/resume/swap, onTrial/subscribed/onGracePeriod) —
 * because that's a well-proven shape to put in front of developers who
 * already know it — but implemented entirely against our own
 * Subscription/SubscriptionInvoice models and M-Pesa, with zero Cashier
 * code underneath. Nothing here talks to any third-party billing processor, ever.
 */
final class SubscriptionService
{
    /** Starts a brand new subscription, in trial if the plan has trial_days. */
    public function subscribe(Tenant $tenant, Plan $plan): Subscription
    {
        $now = now();
        $trialEndsAt = $plan->trial_days > 0 ? $now->clone()->addDays($plan->trial_days) : null;

        return Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => $trialEndsAt !== null ? SubscriptionStatus::Trialing : SubscriptionStatus::Active,
            'trial_ends_at' => $trialEndsAt,
            'current_period_start' => $now,
            'current_period_end' => $trialEndsAt ?? $now->clone()->addDays($plan->billing_interval_days),
        ]);
    }

    /**
     * Swap to a different plan, effective at the CURRENT period's end —
     * no proration, mirroring the simplest of Cashier's swap semantics
     * rather than its full proration engine, which needs a real payment
     * processor's own invoicing behind it to do correctly.
     */
    public function swap(Subscription $subscription, Plan $newPlan): Subscription
    {
        $subscription->update(['plan_id' => $newPlan->id]);

        return $subscription->fresh();
    }

    public function cancel(Subscription $subscription, bool $atPeriodEnd = true): Subscription
    {
        if ($atPeriodEnd) {
            $subscription->update(['cancel_at_period_end' => true]);

            return $subscription->fresh();
        }

        $subscription->update([
            'status' => SubscriptionStatus::Canceled,
            'canceled_at' => now(),
            'cancel_at_period_end' => false,
        ]);

        return $subscription->fresh();
    }

    public function resume(Subscription $subscription): Subscription
    {
        $subscription->update([
            'cancel_at_period_end' => false,
            'canceled_at' => null,
            'status' => $subscription->current_period_end->isFuture() ? SubscriptionStatus::Active : $subscription->status,
        ]);

        return $subscription->fresh();
    }

    /**
     * Creates the next invoice for a subscription and immediately fires
     * an STK Push for it — called by GenerateDueSubscriptionInvoicesCommand
     * for renewals, but also usable directly (e.g. a manual "retry
     * billing now" action in the superadmin panel).
     */
    public function invoiceNow(Subscription $subscription, string $payerPhone): SubscriptionInvoice
    {
        $invoice = DB::connection('central')->transaction(function () use ($subscription) {
            return SubscriptionInvoice::create([
                'subscription_id' => $subscription->id,
                'tenant_id' => $subscription->tenant_id,
                'amount' => $subscription->plan->price,
                'currency' => $subscription->plan->currency,
                'due_date' => $subscription->current_period_end,
                'status' => SubscriptionInvoiceStatus::Pending,
            ]);
        });

        InitiateSubscriptionStkPushJob::dispatch($invoice->id, $payerPhone)->onQueue('billing');

        return $invoice;
    }
}

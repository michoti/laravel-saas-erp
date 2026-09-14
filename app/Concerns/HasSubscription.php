<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Replaces Laravel Cashier's `Billable` trait on the Tenant model.
 * Deliberately named/shaped to feel familiar to anyone who's used
 * Cashier ($tenant->subscribed(), $tenant->onTrial()), while delegating
 * all actual state changes to App\Services\Billing\SubscriptionService —
 * this trait is a read-only convenience layer over that service and the
 * Subscription model, not a second place billing logic lives.
 */
trait HasSubscription
{
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function invoices(): HasManyThrough
    {
        return $this->hasManyThrough(SubscriptionInvoice::class, Subscription::class);
    }

    public function subscribed(): bool
    {
        return $this->subscription?->active() ?? false;
    }

    public function onTrial(): bool
    {
        return $this->subscription?->onTrial() ?? false;
    }

    public function onGracePeriod(): bool
    {
        return $this->subscription?->onGracePeriod() ?? false;
    }

    public function pastDue(): bool
    {
        return $this->subscription?->pastDue() ?? false;
    }
}

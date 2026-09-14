<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Central-database record of a tenant's billing relationship with the
 * platform. Deliberately Cashier-shaped (status enum, trial/period
 * bookkeeping, cancel-at-period-end) since Cashier's Subscription model
 * is a well-proven design to imitate — but backed by M-Pesa via
 * App\Services\Billing\* against M-Pesa, with no third-party billing
 * dependency at all.
 */
final class Subscription extends Model
{
    protected $connection = 'central';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'cancel_at_period_end' => 'boolean',
            'canceled_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    public function onTrial(): bool
    {
        return $this->status === SubscriptionStatus::Trialing && $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function active(): bool
    {
        return in_array($this->status, [SubscriptionStatus::Trialing, SubscriptionStatus::Active], true);
    }

    public function pastDue(): bool
    {
        return $this->status === SubscriptionStatus::PastDue;
    }

    public function canceled(): bool
    {
        return $this->status === SubscriptionStatus::Canceled;
    }

    public function onGracePeriod(): bool
    {
        return $this->cancel_at_period_end && $this->current_period_end->isFuture();
    }

    /**
     * Subscriptions whose current period has ended (or is about to) and
     * which haven't been canceled — the candidate set
     * GenerateDueSubscriptionInvoicesCommand processes on its daily run.
     * Written with the #[Scope] attribute (Laravel 11+) rather than the
     * legacy `scopeDueForRenewal()` naming convention.
     */
    #[Scope]
    protected function dueForRenewal(Builder $query, \DateTimeInterface $asOf): Builder
    {
        return $query
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trialing])
            ->where('cancel_at_period_end', false)
            ->where('current_period_end', '<=', $asOf);
    }
}

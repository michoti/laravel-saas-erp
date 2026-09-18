<?php

declare(strict_types=1);

namespace Modules\Pos\Policies;

use App\Enums\OrderStatus;
use App\Models\User;
use Modules\Pos\App\Models\Order;

final class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_order');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can('view_order');
    }

    public function create(User $user): bool
    {
        return $user->can('create_order');
    }

    public function update(User $user, Order $order): bool
    {
        // Orders are sync-derived and mostly immutable once paid — only
        // allow status-level edits (e.g. void), never touching totals.
        return $user->can('update_order') && $order->status !== OrderStatus::Voided;
    }

    public function delete(User $user, Order $order): bool
    {
        return false; // orders are never hard-deleted; void instead
    }
}

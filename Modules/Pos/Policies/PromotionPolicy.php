<?php

declare(strict_types=1);

namespace Modules\Pos\Policies;

use App\Models\User;
use Modules\Pos\App\Models\Promotion;

final class PromotionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_promotion');
    }

    public function view(User $user, Promotion $promotion): bool
    {
        return $user->can('view_promotion');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_promotions');
    }

    public function update(User $user, Promotion $promotion): bool
    {
        return $user->can('manage_promotions');
    }

    public function delete(User $user, Promotion $promotion): bool
    {
        return $user->can('manage_promotions');
    }
}

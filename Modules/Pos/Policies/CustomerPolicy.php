<?php

declare(strict_types=1);

namespace Modules\Pos\Policies;

use App\Models\User;
use Modules\Pos\App\Models\Customer;

final class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_customer');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can('view_customer');
    }

    public function create(User $user): bool
    {
        return $user->can('create_customer');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can('update_customer');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('delete_customer');
    }
}

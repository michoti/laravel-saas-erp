<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

final class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_role');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('view_role');
    }

    public function create(User $user): bool
    {
        return $user->can('create_role');
    }

    public function update(User $user, Role $role): bool
    {
        // The Owner role is the tenant's own root role — protect it from
        // being neutered by another Owner (or a misclick) into a role with
        // no one left able to manage the account.
        return $user->can('update_role') && $role->name !== 'Owner';
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('delete_role') && ! in_array($role->name, ['Owner', 'Manager', 'Cashier', 'Accountant'], true);
    }
}

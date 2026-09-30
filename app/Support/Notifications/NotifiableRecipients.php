<?php

declare(strict_types=1);

namespace App\Support\Notifications;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves WHO gets notified based on Spatie permissions within the
 * CURRENTLY ACTIVE tenant connection — never a flat, hand-picked array of
 * users. This is what keeps an ERP notification from reaching someone who
 * shouldn't see it: a low-stock alert only reaches users who can actually
 * act on inventory, a billing notice only reaches users who can see
 * billing, and so on, decided at send time from each tenant's own roles
 * table rather than from a name/email list that can drift out of sync with
 * reality.
 *
 * Must be called while tenancy is already initialized (User::query() would
 * otherwise run against the central connection, or throw, depending on
 * what's currently bound) — in practice this means calling it from inside
 * a tenant request or from inside `tenancy()->run($tenant, fn () => ...)`.
 */
final class NotifiableRecipients
{
    /**
     * @return Collection<int, User>
     */
    public static function withPermission(string $permission): Collection
    {
        return User::query()
            ->permission($permission) // spatie/laravel-permission's own query scope
            ->get();
    }

    /**
     * @param  list<string>  $permissions
     * @return Collection<int, User>
     */
    public static function withAnyPermission(array $permissions): Collection
    {
        return User::query()
            ->permission($permissions)
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    public static function withRole(string $role): Collection
    {
        return User::query()
            ->role($role)
            ->get();
    }
}

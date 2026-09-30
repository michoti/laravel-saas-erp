<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Lives in the TENANT database. Roles/permissions (spatie/laravel-permission)
 * are therefore naturally tenant-scoped — there is no shared `roles` table
 * to leak across tenants.
 *
 * Two-factor authentication has been deliberately removed (it was never
 * actually wired up — Filament's own Login page authenticates directly and
 * never invoked Fortify's 2FA challenge, so the `TwoFactorAuthenticatable`
 * trait and its columns were dead weight giving a false sense of security).
 * The columns themselves are dropped by a tenant migration; see
 * database/migrations/tenant/..._drop_two_factor_columns_from_users_table.php.
 */
final class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use HasUuids;
    use Notifiable;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // { channels: ['mail','database','broadcast'], webhook_url: ... }
            // Read by TenantAwareNotification::via() to let each user pick
            // their own delivery channels. Missing/empty falls back to
            // ['database', 'mail'] — see that class for the exact default.
            'notification_preferences' => 'array',
        ];
    }

    /**
     * @return list<string>
     */
    public function preferredNotificationChannels(): array
    {
        $channels = $this->notification_preferences['channels'] ?? null;

        return is_array($channels) && $channels !== []
            ? $channels
            : ['database', 'mail'];
    }

    /**
     * Gates entry to the tenant-facing ("app") panel specifically:
     *
     * - Every real tenant user is assigned one of the roles seeded by
     *   TenantDatabaseSeeder (Owner/Manager/Cashier/Accountant). A user
     *   with no role at all — e.g. a half-provisioned account — is
     *   refused rather than let in with no permissions and a blank UI.
     * - A tenant with no subscription, or a fully CANCELED one, cannot
     *   log in at all. PastDue/Unpaid/Trialing are deliberately still
     *   allowed in, so the tenant can see billing status and pay — that
     *   distinction belongs to module-level gating (EnsureModuleIsEnabled),
     *   not to whether they can reach the panel at all.
     *
     * Other panels (the central "admin" panel) authenticate through the
     * separate `platform_admin` guard/provider entirely, so this model is
     * never even asked about them in practice; the explicit `true` below
     * just avoids silently blocking a panel this model was never meant to
     * gate in the first place.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'app') {
            return true;
        }

        if (! $this->hasAnyRole(['Owner', 'Manager', 'Cashier', 'Accountant'])) {
            return false;
        }

        $tenant = tenancy()->tenant;

        return $tenant !== null
            && $tenant->subscription !== null
            && $tenant->subscription->status !== SubscriptionStatus::Canceled;
    }
}

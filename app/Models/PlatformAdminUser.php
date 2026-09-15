<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Lives in the CENTRAL database. Distinct from the tenant-scoped App\Models\User
 * on purpose: platform staff (Anthropic-style "super admin") manage tenants,
 * billing, and module entitlements, and must never be confused with — or
 * accidentally scoped by — spatie/laravel-permission roles that live inside
 * each tenant's own database. A simple boolean flag is sufficient here; see
 * ARCHITECTURE.md for why RBAC nuance belongs only inside tenant DBs.
 */
final class PlatformAdminUser extends Authenticatable implements FilamentUser
{
    use HasApiTokens;
    use HasFactory;
    use HasUuids;
    use Notifiable;

    protected $connection = 'central';

    protected $table = 'platform_admin_users';

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'is_super_admin' => 'boolean',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->is_super_admin;
    }
}

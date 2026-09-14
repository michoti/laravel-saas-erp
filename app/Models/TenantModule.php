<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Central-database registry of which modules (POS, Invoicing, CRM, ...)
 * a tenant has installed/activated, driving both Filament resource
 * visibility and API route access (see EnsureModuleIsEnabled middleware).
 */
final class TenantModule extends Model
{
    protected $connection = 'central';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'enabled_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

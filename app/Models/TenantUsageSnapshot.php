<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Because tenancy is database-per-tenant, the superadmin dashboard cannot
 * cheaply run a live cross-tenant SUM/COUNT query — there is no shared
 * `orders` table to aggregate. Instead, RefreshTenantUsageSnapshotsCommand
 * loops each tenant DB nightly (and on-demand) and writes one row per
 * tenant per day here, in the CENTRAL database, which every superadmin
 * chart/widget then reads from with a single fast query.
 */
final class TenantUsageSnapshot extends Model
{
    protected $connection = 'central';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'gross_sales' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

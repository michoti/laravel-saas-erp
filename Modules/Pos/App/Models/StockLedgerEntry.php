<?php

declare(strict_types=1);

namespace Modules\Pos\App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Pos\Database\Factories\StockLedgerEntryFactory;

/**
 * Append-only. There is no `updated_at`, no update()/delete() usage
 * anywhere in application code, and the database enforces this with a
 * BEFORE UPDATE OR DELETE trigger (see the create_stock_ledger_table
 * migration).
 */
final class StockLedgerEntry extends Model
{
    use HasFactory;
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'stock_ledger';

    protected static function newFactory(): StockLedgerEntryFactory
    {
        return StockLedgerEntryFactory::new();
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'movement_type' => StockMovementType::class,
            'quantity_delta' => 'decimal:4',
            'occurred_at' => 'datetime',
            'client_created_at' => 'datetime',
            'client_updated_at' => 'datetime',
            'synced_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

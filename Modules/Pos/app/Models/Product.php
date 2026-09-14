<?php

declare(strict_types=1);

namespace Modules\Pos\App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Pos\Database\Factories\ProductFactory;

final class Product extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'is_active' => 'boolean',
            'track_inventory' => 'boolean',
            'client_created_at' => 'datetime',
            'client_updated_at' => 'datetime',
            'synced_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function stockLedgerEntries(): HasMany
    {
        return $this->hasMany(StockLedgerEntry::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    /**
     * The single best (largest discount) currently-active promotion for
     * this product, if any — evaluated in PHP against an eager-loaded
     * `promotions` relation rather than a fresh query, so scanning a cart
     * full of items at checkout never N+1s against the promotions table.
     */
    public function activePromotion(): ?Promotion
    {
        return $this->promotions
            ->filter(fn (Promotion $promotion): bool => $promotion->isCurrentlyActive())
            ->sortByDesc(fn (Promotion $promotion): float => $this->unit_price - $promotion->apply((float) $this->unit_price))
            ->first();
    }

    /**
     * Derived stock-on-hand — never stored, always summed from the
     * append-only ledger, keeping the write path free of race conditions.
     */
    public function stockOnHand(): float
    {
        return (float) $this->stockLedgerEntries()->sum('quantity_delta');
    }
}

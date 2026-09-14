<?php

declare(strict_types=1);

namespace Modules\Pos\Models;

use App\Enums\PromotionDiscountType;
use Illuminate\Database\Eloquent\Concerns\HasVersion7Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Pos\Database\Factories\PromotionFactory;

final class Promotion extends Model
{
    use HasVersion7Uuids;
    use HasFactory;

    protected static function newFactory(): PromotionFactory
    {
        return PromotionFactory::new();
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'discount_type' => PromotionDiscountType::class,
            'discount_value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isCurrentlyActive(): bool
    {
        return $this->is_active
            && $this->starts_at->isPast()
            && $this->ends_at->isFuture();
    }

    /** Returns the discounted unit price for a given base price — delegates to the enum, single source of truth for the discount math. */
    public function apply(float $unitPrice): float
    {
        return $this->discount_type->apply($unitPrice, (float) $this->discount_value);
    }
}

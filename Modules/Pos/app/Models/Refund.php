<?php

declare(strict_types=1);

namespace Modules\Pos\Models;

use App\Enums\RefundMethod;
use Illuminate\Database\Eloquent\Concerns\HasVersion7Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Refund extends Model
{
    use HasVersion7Uuids;

    public const UPDATED_AT = null; // refunds are immutable once processed

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'method' => RefundMethod::class,
            'amount' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'processed_by_user_id');
    }
}

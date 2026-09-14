<?php

declare(strict_types=1);

namespace Modules\Pos\App\Models;

use App\Enums\RefundMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Refund extends Model
{
    use HasUuids;

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
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }
}

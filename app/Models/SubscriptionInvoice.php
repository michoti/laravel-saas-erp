<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubscriptionInvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SubscriptionInvoice extends Model
{
    protected $connection = 'central';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionInvoiceStatus::class,
            'amount' => 'decimal:2',
            'due_date' => 'datetime',
            'stk_pushed_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Pos\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Pos\Database\Factories\PaymentFactory;

final class Payment extends Model
{
    use HasFactory;
    use HasUuids;

    protected static function newFactory(): PaymentFactory
    {
        return PaymentFactory::new();
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'stk_pushed_at' => 'datetime',
            'client_created_at' => 'datetime',
            'client_updated_at' => 'datetime',
            'synced_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isMpesa(): bool
    {
        return $this->method === PaymentMethod::Mpesa;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Pos\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Pos\Database\Factories\OrderFactory;

final class Order extends Model
{
    use HasFactory;
    use HasUuids;

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'order_date' => 'datetime',
            'client_created_at' => 'datetime',
            'client_updated_at' => 'datetime',
            'synced_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function totalRefunded(): float
    {
        return (float) $this->refunds()->sum('amount');
    }

    public function hasInvoiceNumber(): bool
    {
        return ! is_null($this->invoice_number);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Pos\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Pos\Database\Factories\CustomerFactory;

final class Customer extends Model
{
    use HasFactory;
    use HasUuids;

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'store_credit_balance' => 'decimal:2',
            'client_created_at' => 'datetime',
            'client_updated_at' => 'datetime',
            'synced_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function storeCreditEntries(): HasMany
    {
        return $this->hasMany(StoreCreditLedgerEntry::class);
    }
}

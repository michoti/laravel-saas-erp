<?php

declare(strict_types=1);

namespace Modules\Pos\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Pos\App\Models\Product;
use Modules\Pos\App\Models\StockLedgerEntry;

/** @extends Factory<StockLedgerEntry> */
final class StockLedgerEntryFactory extends Factory
{
    protected $model = StockLedgerEntry::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'movement_type' => 'purchase',
            'quantity_delta' => fake()->numberBetween(20, 200),
            'occurred_at' => now()->subDays(fake()->numberBetween(1, 60)),
            'version' => 1,
        ];
    }

    public function sale(): self
    {
        return $this->state(fn (): array => [
            'movement_type' => 'sale',
            'quantity_delta' => -fake()->numberBetween(1, 5),
        ]);
    }
}

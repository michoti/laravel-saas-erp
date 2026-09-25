<?php

declare(strict_types=1);

namespace Modules\Pos\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Pos\App\Models\Promotion;

/** @extends Factory<Promotion> */
final class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Weekend Sale', 'Clearance', 'Member Special', 'Festive Discount']),
            'discount_type' => 'percentage',
            'discount_value' => fake()->randomElement([10, 15, 20, 25]),
            'product_id' => null, // storewide by default
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addWeek(),
            'is_active' => true,
        ];
    }

    public function forProduct(string $productId): self
    {
        return $this->state(fn (): array => ['product_id' => $productId]);
    }

    public function expired(): self
    {
        return $this->state(fn (): array => [
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subWeek(),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Pos\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Pos\App\Models\Product;

/** @extends Factory<Product> */
final class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $categories = ['Beverages', 'Snacks', 'Household', 'Personal Care', 'Produce', 'Bakery'];

        return [
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####??')),
            'barcode' => fake()->unique()->ean13(),
            'name' => ucfirst(fake()->words(3, true)),
            'category' => fake()->randomElement($categories),
            'unit_price' => fake()->randomFloat(2, 20, 2500),
            'tax_rate' => fake()->randomElement([0, 0.16]),
            'currency' => 'KES',
            'is_active' => true,
            'track_inventory' => true,
            'version' => 1,
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function withImage(): self
    {
        return $this->state(fn (): array => ['image_path' => 'product-images/placeholder-'.fake()->numberBetween(1, 6).'.jpg']);
    }
}

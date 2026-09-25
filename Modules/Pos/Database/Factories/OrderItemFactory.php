<?php

declare(strict_types=1);

namespace Modules\Pos\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Pos\App\Models\Order;
use Modules\Pos\App\Models\OrderItem;
use Modules\Pos\App\Models\Product;

/** @extends Factory<OrderItem> */
final class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->randomFloat(2, 20, 2500);
        $subtotal = $quantity * $unitPrice;
        $tax = round($subtotal * 0.16, 2);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_name_snapshot' => fake()->words(2, true),
            'product_sku_snapshot' => strtoupper(fake()->bothify('SKU-####')),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => 0.16,
            'tax_amount' => $tax,
            'discount_amount' => 0,
            'line_subtotal' => $subtotal,
            'line_total' => $subtotal + $tax,
            'version' => 1,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Pos\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Pos\App\Models\Order;

/** @extends Factory<Order> */
final class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $orderDate = fake()->dateTimeBetween('-60 days', 'now');
        $subtotal = fake()->randomFloat(2, 200, 15000);
        $tax = round($subtotal * 0.16, 2);

        return [
            'local_reference' => 'DEMO-'.fake()->unique()->numerify('####'),
            'invoice_number' => 'INV-'.fake()->unique()->numerify('######'),
            'device_id' => null,
            'subtotal' => $subtotal,
            'tax_total' => $tax,
            'discount_total' => 0,
            'grand_total' => $subtotal + $tax,
            'currency' => 'KES',
            'status' => 'paid',
            'order_date' => $orderDate,
            'client_created_at' => $orderDate,
            'client_updated_at' => $orderDate,
            'version' => 1,
            'synced_at' => $orderDate,
        ];
    }
}

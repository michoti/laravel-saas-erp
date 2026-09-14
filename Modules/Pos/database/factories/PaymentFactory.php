<?php

declare(strict_types=1);

namespace Modules\Pos\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Pos\App\Models\Order;
use Modules\Pos\App\Models\Payment;

/** @extends Factory<Payment> */
final class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'method' => 'cash',
            'status' => 'completed',
            'amount' => fake()->randomFloat(2, 200, 15000),
            'currency' => 'KES',
            'paid_at' => now(),
            'version' => 1,
        ];
    }

    public function mpesa(): self
    {
        return $this->state(fn (): array => [
            'method' => 'mpesa',
            'status' => 'completed',
            'payer_phone' => '2547'.fake()->numberBetween(10000000, 99999999),
            'mpesa_receipt_number' => strtoupper(fake()->bothify('??######')),
        ]);
    }
}

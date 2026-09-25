<?php

declare(strict_types=1);

namespace Modules\Pos\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Pos\App\Models\Customer;

/** @extends Factory<Customer> */
final class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '2547'.fake()->numberBetween(10000000, 99999999),
            'email' => fake()->unique()->safeEmail(),
            'store_credit_balance' => 0,
            'version' => 1,
        ];
    }
}

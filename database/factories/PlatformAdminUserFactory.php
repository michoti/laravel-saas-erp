<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PlatformAdminUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<PlatformAdminUser> */
final class PlatformAdminUserFactory extends Factory
{
    protected $model = PlatformAdminUser::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'is_super_admin' => true,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PlatformAdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class PlatformAdminSeeder extends Seeder
{
    public function run(): void
    {
        PlatformAdminUser::query()->updateOrCreate(
            ['email' => 'superadmin@platform.test'],
            [
                'name' => 'Platform Super Admin',
                'password' => Hash::make('password'),
                'is_super_admin' => true,
            ]
        );
    }
}

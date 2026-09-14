<?php

declare(strict_types=1);

namespace Modules\Pos\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permission names follow the `{action}_{resource}` convention Filament's
 * resource policies check by default (see app/Policies/*), so a permission
 * defined here automatically gates the matching Filament Resource action
 * with no extra wiring required.
 */
final class PosPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // POS domain
            'view_any_product', 'view_product', 'create_product', 'update_product', 'delete_product',
            'view_any_order', 'view_order', 'create_order', 'update_order',
            'view_any_payment', 'view_payment',
            'view_any_stock_ledger_entry',
            'view_reports', 'export_reports',
            'access_pos_terminal',
            'process_refund',

            // Customers & promotions
            'view_any_customer', 'view_customer', 'create_customer', 'update_customer', 'delete_customer',
            'view_any_promotion', 'view_promotion', 'manage_promotions',

            // Administration
            'view_any_user', 'view_user', 'create_user', 'update_user', 'delete_user',
            'view_any_role', 'view_role', 'create_role', 'update_role', 'delete_role',
            'manage_settings',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

<?php

declare(strict_types=1);

namespace Modules\Pos\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Four role tiers per tenant, from full control down to read-only:
 *
 *   Owner      — everything, including staff/role management, promotions,
 *                and settings. Typically the account that signed up.
 *   Manager    — full run of the shop floor (products, orders, payments,
 *                refunds, promotions, reports) but cannot manage staff
 *                accounts, roles, or tenant settings.
 *   Cashier    — front-of-house only: POS terminal, ring up sales, look up
 *                stock/products/customers. Cannot edit the catalog, run
 *                promotions, or see back-office reports. CAN process
 *                returns at the till (process_refund) since that's a
 *                normal front-of-house task in most retail settings.
 *   Accountant — read-only across orders/payments/reports for reconciliation
 *                and exports; cannot touch the catalog, promotions, or
 *                process sales/refunds.
 */
final class PosRoleSeeder extends Seeder
{
    public function run(): void
    {
        $owner = Role::findOrCreate('Owner', 'web');
        $owner->syncPermissions(Permission::all());

        $manager = Role::findOrCreate('Manager', 'web');
        $manager->syncPermissions([
            'view_any_product', 'view_product', 'create_product', 'update_product', 'delete_product',
            'view_any_order', 'view_order', 'create_order', 'update_order',
            'view_any_payment', 'view_payment',
            'view_any_stock_ledger_entry',
            'view_reports', 'export_reports',
            'access_pos_terminal', 'process_refund',
            'view_any_customer', 'view_customer', 'create_customer', 'update_customer', 'delete_customer',
            'view_any_promotion', 'view_promotion', 'manage_promotions',
        ]);

        $cashier = Role::findOrCreate('Cashier', 'web');
        $cashier->syncPermissions([
            'view_any_product', 'view_product',
            'view_any_order', 'view_order', 'create_order',
            'view_any_payment', 'view_payment',
            'access_pos_terminal', 'process_refund',
            'view_any_customer', 'view_customer', 'create_customer',
            'view_any_promotion', 'view_promotion',
        ]);

        $accountant = Role::findOrCreate('Accountant', 'web');
        $accountant->syncPermissions([
            'view_any_order', 'view_order',
            'view_any_payment', 'view_payment',
            'view_reports', 'export_reports',
            'view_any_customer', 'view_customer',
        ]);
    }
}

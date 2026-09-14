<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Pos\Database\Seeders\PosPermissionSeeder;
use Modules\Pos\Database\Seeders\PosRoleSeeder;
use Modules\Pos\DataTransferObjects\CartLineData;
use Modules\Pos\DataTransferObjects\PaymentLineData;
use Modules\Pos\Models\Customer;
use Modules\Pos\Models\Product;
use Modules\Pos\Models\Promotion;
use Modules\Pos\Models\StockLedgerEntry;
use Modules\Pos\Services\PosCheckoutService;

/**
 * Runs INSIDE a tenant's own database context — invoked automatically for
 * every tenant by `php artisan tenants:seed` (see the `seeder_parameters`
 * in config/tenancy.php, which points at this class by name). Never run
 * this directly with `db:seed`; it will seed whichever connection is
 * currently active, which is only correct when stancl/tenancy has already
 * initialized a tenant for you.
 */
final class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PosPermissionSeeder::class,
            PosRoleSeeder::class,
        ]);

        $owner = User::factory()->create(['name' => 'Store Owner', 'email' => 'owner@demo.test']);
        $owner->assignRole('Owner');

        $manager = User::factory()->create(['name' => 'Store Manager', 'email' => 'manager@demo.test']);
        $manager->assignRole('Manager');

        $cashier = User::factory()->create(['name' => 'Front Till Cashier', 'email' => 'cashier@demo.test']);
        $cashier->assignRole('Cashier');

        $accountant = User::factory()->create(['name' => 'Store Accountant', 'email' => 'accountant@demo.test']);
        $accountant->assignRole('Accountant');

        $this->command?->info('Demo users created (password for all: "password"): owner@demo.test, manager@demo.test, cashier@demo.test, accountant@demo.test');

        // Catalog + opening stock
        $products = Product::factory()->count(40)->create();
        $products->each(function (Product $product): void {
            StockLedgerEntry::factory()->for($product)->create([
                'quantity_delta' => fake()->numberBetween(20, 150),
                'movement_type' => 'purchase',
                'occurred_at' => now()->subDays(30),
            ]);
        });

        // A couple of products deliberately left low-stock to exercise the
        // low-stock badge/widget/filter out of the box.
        $products->take(3)->each(function (Product $product): void {
            StockLedgerEntry::factory()->for($product)->sale()->create([
                'quantity_delta' => -1 * fake()->numberBetween(15, 25),
                'occurred_at' => now()->subDay(),
            ]);
        });

        $customers = Customer::factory()->count(15)->create();

        Promotion::factory()->create([
            'name' => 'Grand Opening Sale',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'product_id' => null,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addWeek(),
        ]);

        // Simulate real checkouts through PosCheckoutService (not raw
        // factory inserts) so invoice numbering, the stock ledger, and
        // payment records are exercised exactly as they would be in
        // production — the seeded data is a genuine dry run of the system,
        // not a shortcut around it.
        $checkoutService = app(PosCheckoutService::class);
        $cashierIds = [$owner->id, $manager->id, $cashier->id];

        for ($i = 0; $i < 25; $i++) {
            $lineProducts = $products->random(random_int(1, 4))->values();
            $total = 0.0;

            $cartLines = $lineProducts->map(function (Product $p) use (&$total): CartLineData {
                $quantity = (float) random_int(1, 3);
                $lineSubtotal = (float) $p->unit_price * $quantity;
                $total += $lineSubtotal + round($lineSubtotal * (float) $p->tax_rate, 2);

                return new CartLineData(productId: $p->id, quantity: $quantity, unitPrice: (float) $p->unit_price);
            });

            $method = fake()->randomElement([PaymentMethod::Cash, PaymentMethod::Cash, PaymentMethod::Mpesa]);

            try {
                $checkoutService->checkout(
                    cartLines: $cartLines,
                    paymentLines: collect([
                        new PaymentLineData(
                            method: $method,
                            amount: round($total, 2),
                            payerPhone: $method === PaymentMethod::Mpesa ? '254712345678' : null,
                        ),
                    ]),
                    customerId: fake()->boolean(60) ? $customers->random()->id : null,
                    cashierUserId: fake()->randomElement($cashierIds),
                );
            } catch (\RuntimeException) {
                // Skip the rare case a random cart totals to zero, etc — demo data only.
                continue;
            }
        }

        $this->command?->info('Seeded 40 products, 15 customers, 1 promotion, and ~25 demo orders via PosCheckoutService.');
    }
}

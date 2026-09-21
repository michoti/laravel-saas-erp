<?php

declare(strict_types=1);

namespace Modules\Pos\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Pos\Console\EnsureStockLedgerPartitionsCommand;
use Modules\Pos\Console\ReconcilePendingMpesaPaymentsCommand;
use Modules\Pos\Console\RefreshDashboardReadModelsCommand;
use Modules\Pos\App\Models\Customer;
use Modules\Pos\App\Models\Order;
use Modules\Pos\App\Models\Payment;
use Modules\Pos\App\Models\Product;
use Modules\Pos\App\Models\Promotion;
use Modules\Pos\App\Models\StockLedgerEntry;
use Modules\Pos\Policies\CustomerPolicy;
use Modules\Pos\Policies\OrderPolicy;
use Modules\Pos\Policies\PaymentPolicy;
use Modules\Pos\Policies\ProductPolicy;
use Modules\Pos\Policies\PromotionPolicy;
use Modules\Pos\Policies\StockLedgerEntryPolicy;
use Modules\Pos\Services\Mpesa\MpesaPaymentResolver;
use Modules\Pos\Services\Mpesa\PosMpesaGateway;

final class PosServiceProvider extends ServiceProvider
{
    protected string $name = 'Pos';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->name, 'Database/Migrations'));
        $this->loadViewsFrom(module_path($this->name, 'Resources/views'), 'pos');
        $this->registerRoutes();
        $this->registerPolicies();

        if ($this->app->runningInConsole()) {
            $this->commands([
                RefreshDashboardReadModelsCommand::class,
                ReconcilePendingMpesaPaymentsCommand::class,
                EnsureStockLedgerPartitionsCommand::class,
            ]);
        }
    }

    private function registerPolicies(): void
    {
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(StockLedgerEntry::class, StockLedgerEntryPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Promotion::class, PromotionPolicy::class);
    }

    public function register(): void
    {
        $this->app->bind(PosMpesaGateway::class);
        $this->app->singleton(MpesaPaymentResolver::class);
    }

    private function registerRoutes(): void
    {
        Route::middleware(['api', 'tenant', 'module:pos'])
            ->prefix('api/pos')
            ->group(module_path($this->name, 'Routes/api.php'));

        // Public M-Pesa webhook: no tenant middleware (tenant is resolved
        // from the payment row's owning connection inside the controller),
        // no Sanctum auth — gated by an IP-allowlist middleware instead.
        Route::middleware(['api'])
            ->group(module_path($this->name, 'Routes/webhooks.php'));
    }
}

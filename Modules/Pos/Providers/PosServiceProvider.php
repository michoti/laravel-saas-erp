<?php

declare(strict_types=1);

namespace Modules\Pos\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

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
                \Modules\Pos\Console\RefreshDashboardReadModelsCommand::class,
                \Modules\Pos\Console\ReconcilePendingMpesaPaymentsCommand::class,
            ]);
        }
    }

    private function registerPolicies(): void
    {
        \Illuminate\Support\Facades\Gate::policy(\Modules\Pos\Models\Product::class, \Modules\Pos\Policies\ProductPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\Modules\Pos\Models\Order::class, \Modules\Pos\Policies\OrderPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\Modules\Pos\Models\Payment::class, \Modules\Pos\Policies\PaymentPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\Modules\Pos\Models\StockLedgerEntry::class, \Modules\Pos\Policies\StockLedgerEntryPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\Modules\Pos\Models\Customer::class, \Modules\Pos\Policies\CustomerPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\Modules\Pos\Models\Promotion::class, \Modules\Pos\Policies\PromotionPolicy::class);
    }

    public function register(): void
    {
        $this->app->singleton(\Modules\Pos\Services\Mpesa\MpesaClient::class);
        $this->app->singleton(\Modules\Pos\Services\Mpesa\MpesaPaymentResolver::class);
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

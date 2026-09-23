<?php

namespace App\Providers;

use App\Http\Middleware\InitializeTenancyForLivewire;
use App\Services\Billing\SubscriptionMpesaGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        }
        // Singleton, unlike Modules\Pos\Services\Mpesa\PosMpesaGateway
        // (bound fresh per resolution) — the platform's OWN billing
        // credentials don't change per tenant/request the way a tenant's
        // own POS credentials do.
        $this->app->singleton(SubscriptionMpesaGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Strict loading is enforced OUTSIDE production only — a lazy
        // relationship access throws here in local/staging/CI (surfacing
        // N+1s before they ship) rather than silently issuing an extra
        // query the way it would in production, where availability
        // matters more than catching the mistake immediately.
        Model::shouldBeStrict(! $this->app->isProduction());
        Model::unguard(false); 

         if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        \Illuminate\Support\Facades\Gate::policy(\Spatie\Permission\Models\Role::class, \App\Policies\RolePolicy::class);

        // Livewire::setUpdateRoute(function ($handle, $path) {
        //     return Route::post($path, $handle)
        //         ->middleware([InitializeTenancyForLivewire::class, 'web']);
        // });
    }
}

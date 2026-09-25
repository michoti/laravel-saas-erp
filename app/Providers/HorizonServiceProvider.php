<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        Horizon::routeSmsNotificationsTo(config('services.horizon.alert_phone'));
        Horizon::routeMailNotificationsTo(config('services.horizon.alert_email'));

    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        // Deliberately ignores the injected $user (resolved from Laravel's
        // DEFAULT guard, which floats to whichever tenant's `users` table
        // is currently bound — or no real tenant database at all, since
        // /horizon sits outside the `tenant` middleware group entirely).
        // Horizon is central platform infrastructure, so authorization
        // must check the central `platform_admin` guard explicitly.
        Gate::define('viewHorizon', fn (): bool => auth('platform_admin')->user()?->is_super_admin === true);
    }
}

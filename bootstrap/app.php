<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureModuleIsEnabled;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Tenant-resolution middleware group, attached explicitly to the
        // `tenant` route group in routes/api.php rather than globally, so
        // central/admin routes are never accidentally tenant-scoped.
        //
        // Deliberately does NOT include EnsureModuleIsEnabled: this group
        // is used by tenant routes that have nothing to do with any single
        // module (e.g. /api/user). Module gating is applied per-route-group
        // instead, via the 'module:xxx' alias below — see
        // Modules/Pos/Providers/PosServiceProvider::registerRoutes() and
        // AppPanelProvider's panel-level middleware for the two places
        // that actually need it.
        $middleware->appendToGroup('tenant', [
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
        ]);

        $middleware->alias([
            'module' => EnsureModuleIsEnabled::class,
            'mpesa.verify_source' => \App\Http\Middleware\VerifyMpesaWebhookSource::class,
        ]);

        // '*' would trust the client-supplied X-Forwarded-For header from
        // ANY source — which would let an attacker spoof their way past
        // VerifyMpesaWebhookSource's IP allowlist simply by setting that
        // header themselves. Trust only the actual proxy/load-balancer
        // IPs in front of this app (set via TRUSTED_PROXIES), never a
        // wildcard, for any deployment where an IP-based check matters.
        $middleware->trustProxies(at: array_filter(explode(',', (string) env('TRUSTED_PROXIES', ''))));

        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('horizon*', 'telescope*')) {
                return route('filament.admin.auth.login');
            }
            return route('filament.app.auth.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport([
            \Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedException::class,
        ]);
    })
    ->create();

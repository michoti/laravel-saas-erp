<?php

use App\Http\Middleware\EnsureModuleIsEnabled;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureModuleIsEnabled;
use App\Models\Tenant;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Modules\Pos\Filament\Pages\Dashboard;
use Modules\Pos\Filament\Pages\MpesaSettings;
use Modules\Pos\Filament\Pages\PosTerminal;
use Modules\Pos\Filament\Widgets\PendingMpesaWidget;
use Modules\Pos\Filament\Widgets\SalesTodayStatsWidget;
use Modules\Pos\Filament\Widgets\SalesTrendChartWidget;
use Modules\Pos\Filament\Widgets\StockAlertsTableWidget;
use Modules\Pos\Filament\Widgets\TopProductsChartWidget;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/**
 * Tenant-facing panel, mounted at /app on every tenant SUBDOMAIN. Resolved
 * per-request by `InitializeTenancyByDomain` — this is Filament sitting on
 * top of stancl/tenancy's database-per-tenant model rather than Filament's
 * own (single-database) tenancy feature, which doesn't fit our isolation
 * requirement. Branding (colors/logo/font) is pulled from the resolved
 * Tenant's `theme` column at boot time, per request.
 *
 * CAVEAT: `panel()` runs once per request under classic PHP-FPM, which is
 * where currentTenantTheme()'s use of request()->getHost() is safe. Under
 * Laravel Octane (persistent workers), Filament's panel registration can
 * be memoized across requests within the same worker, which would leak
 * one tenant's branding into another's on that worker. If deploying on
 * Octane, move this lookup into a render-time hook (e.g. a view composer
 * or Filament's `renderHook`) instead of panel-registration time.
 *
 * MIDDLEWARE ORDER: tenancy identification is listed FIRST in the
 * `isPersistent: true` array, ahead of session/cookie middleware.
 * TenancyServiceProvider::makeTenancyMiddlewareHighestPriority() reorders
 * these to run first for normally-routed requests via Laravel's middleware
 * priority system, but the login form (and every other Livewire action in
 * this panel) posts to the shared Livewire update endpoint, which Filament
 * runs its *persistent* middleware against directly — declared order, not
 * priority-sorted order. If tenancy identification ran after StartSession
 * here, a Livewire action could execute against the wrong database
 * connection despite the priority config elsewhere. Keep this order.
 */
final class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $theme = $this->currentTenantTheme();

        return $panel
            ->id('app')
            ->path('app')
            ->authGuard('web')
            ->login()
            // Filament's own profile page: lets the authenticated tenant
            // user update their name/email and, with current-password
            // confirmation, their password. No custom page needed.
            ->profile()
            ->colors(['primary' => $theme['primary_color'] ?? Color::Amber])
            ->viteTheme('resources/css/filament/app/theme.css')
            ->brandName($theme['brand_name'] ?? 'POS')
            ->brandLogo(filled($theme['logo_url'] ?? null) ? Storage::url($theme['logo_url']) : null)
            ->discoverResources(in: app_path('Filament/Tenant/Resources'), for: 'App\\Filament\\Tenant\\Resources')
            ->discoverResources(in: base_path('Modules/Pos/Filament/Resources'), for: 'Modules\\Pos\\Filament\\Resources')
            ->discoverWidgets(in: base_path('Modules/Pos/Filament/Widgets'), for: 'Modules\\Pos\\Filament\\Widgets')
            ->pages([Dashboard::class, PosTerminal::class, MpesaSettings::class])
            ->widgets([
                SalesTodayStatsWidget::class,
                SalesTrendChartWidget::class,
                TopProductsChartWidget::class,
                StockAlertsTableWidget::class,
                PendingMpesaWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Point of Sale'),
                NavigationGroup::make('Administration'),
            ])
            ->middleware([
                // Tenancy identification MUST run before anything that
                // touches the database or session, on every request —
                // including Livewire's shared update endpoint. See the
                // class docblock above.
                PreventAccessFromCentralDomains::class,
                InitializeTenancyByDomain::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                EnsureModuleIsEnabled::class.':pos',
            ], isPersistent: true)
            ->authMiddleware([AuthenticateSession::class])
            // Powers the panel's notification bell for the `database`
            // channel (see App\Notifications\TenantAwareNotification and
            // LowStockAlert) — reads from THIS tenant's own `notifications`
            // table (see the tenant-path create_notifications_table
            // migration), never the central one PlatformAdminUser uses.
            ->databaseNotifications()
            ->spa();
    }

    /**
     * Reads the CENTRAL tenants table for the domain currently being
     * requested (the panel's own tenancy middleware hasn't run yet at
     * PanelProvider boot time, so this is a lightweight, cached lookup —
     * not a duplicate `tenancy()->initialize()` call).
     */
    private function currentTenantTheme(): array
    {
        if (app()->runningInConsole()) {
            return [];
        }

        $host = request()?->getHost();

        if ($host === null) {
            return [];
        }

        return Cache::remember(
            "tenant-theme:{$host}",
            now()->addMinutes(15),
            fn () => Tenant::query()->whereHas('domains', fn ($q) => $q->where('domain', $host))->first()?->theme ?? []
        );
    }
}

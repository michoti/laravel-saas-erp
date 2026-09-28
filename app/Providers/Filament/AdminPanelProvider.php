<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Central\Widgets\ModuleAdoptionChart;
use App\Filament\Central\Widgets\MrrTrendChart;
use App\Filament\Central\Widgets\TenantGrowthChart;
use App\Filament\Central\Widgets\TenantStatsOverview;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The platform (superadmin) panel. Deliberately NOT behind the `tenant`
 * middleware group: it runs against the CENTRAL database only.
 *
 * The panel is bound to the central domains, so its routes do not exist on
 * tenant hosts at all. `PreventAccessFromCentralDomains` is intentionally
 * absent: it protects tenant routes from central hosts and would lock this
 * panel out of the central domain.
 */
final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->domains(config('tenancy.central_domains', []))
            ->authGuard('platform_admin')
            ->login()
            ->colors(['primary' => Color::Indigo])
            // ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName('Platform Admin')
            ->discoverResources(in: app_path('Filament/Central/Resources'), for: 'App\\Filament\\Central\\Resources')
            ->discoverPages(in: app_path('Filament/Central/Pages'), for: 'App\\Filament\\Central\\Pages')
            ->discoverWidgets(in: app_path('Filament/Central/Widgets'), for: 'App\\Filament\\Central\\Widgets')
            ->widgets([
                TenantStatsOverview::class,
                TenantGrowthChart::class,
                MrrTrendChart::class,
                ModuleAdoptionChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([AuthenticateSession::class])
            // Dashboard queries read the pre-aggregated TenantUsageSnapshot
            // table (see RefreshTenantUsageSnapshotsCommand).
            ->databaseNotifications()
            ->spa();
    }
}

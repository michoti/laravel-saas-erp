<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Central\Pages\Dashboard;
use App\Filament\Central\Widgets\ModuleAdoptionChart;
use App\Filament\Central\Widgets\MrrTrendChart;
use App\Filament\Central\Widgets\TenantGrowthChart;
use App\Filament\Central\Widgets\TenantStatsOverview;
use App\Models\PlatformAdminUser;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/**
 * The platform (superadmin) panel. Deliberately NOT behind the `tenant`
 * middleware group — it runs against the CENTRAL database only, and
 * `PreventAccessFromCentralDomains` ensures it can never be reached from a
 * tenant subdomain even if someone guesses the /admin path there.
 */
final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->authGuard('platform_admin')
            ->login()
            ->colors(['primary' => Color::Indigo])
            // ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName('Platform Admin')
            ->discoverResources(in: app_path('Filament/Central/Resources'), for: 'App\\Filament\\Central\\Resources')
            ->discoverPages(in: app_path('Filament/Central/Pages'), for: 'App\\Filament\\Central\\Pages')
            ->pages([Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Central/Widgets'), for: 'App\\Filament\\Central\\Widgets')
            ->widgets([
                TenantStatsOverview::class,
                TenantGrowthChart::class,
                MrrTrendChart::class,
                ModuleAdoptionChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                \Illuminate\Session\Middleware\StartSession::class,
                \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                // PreventAccessFromCentralDomains::class,
            ])
            ->authMiddleware([AuthenticateSession::class])
            // Superadmin dashboard queries are backed by the pre-aggregated
            // TenantUsageSnapshot table (see RefreshTenantUsageSnapshotsCommand),
            // so this panel stays fast even with thousands of tenants.
            ->databaseNotifications()
            ->spa();
    }
}

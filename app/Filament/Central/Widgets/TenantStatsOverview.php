<?php

declare(strict_types=1);

namespace App\Filament\Central\Widgets;

use App\Models\Tenant;
use App\Models\TenantUsageSnapshot;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

final class TenantStatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = null; // snapshots refresh hourly — no need to poll every few seconds

    protected function getStats(): array
    {
        // Single cache entry, tagged so the "toggle module" and nightly
        // snapshot-refresh actions can invalidate it explicitly instead of
        // this widget re-querying on every superadmin page view.
        $stats = Cache::tags(['central-dashboard'])->remember('central:tenant-stats-overview', now()->addMinutes(30), function (): array {
            $totalTenants = Tenant::query()->count();
            $activeTrials = Tenant::query()->whereNotNull('trial_ends_at')->where('trial_ends_at', '>=', now())->count();

            $today = TenantUsageSnapshot::query()->whereDate('snapshot_date', now()->toDateString());

            return [
                'total_tenants' => $totalTenants,
                'active_trials' => $activeTrials,
                'orders_today' => (int) $today->sum('order_count'),
                'gross_sales_today' => (float) $today->sum('gross_sales'),
            ];
        });

        return [
            Stat::make('Total tenants', number_format($stats['total_tenants']))
                ->icon('heroicon-o-building-office-2')
                ->color('primary'),

            Stat::make('Active trials', number_format($stats['active_trials']))
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Orders today (all tenants)', number_format($stats['orders_today']))
                ->icon('heroicon-o-shopping-cart')
                ->color('success'),

            Stat::make('Gross sales today', 'KES '.number_format($stats['gross_sales_today'], 2))
                ->icon('heroicon-o-banknotes')
                ->color('success'),
        ];
    }
}

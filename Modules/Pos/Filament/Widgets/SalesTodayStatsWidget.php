<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Pos\App\Models\Order;

final class SalesTodayStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        // Cache is already tenant-namespaced by CacheTenancyBootstrapper —
        // a short TTL keeps this near-real-time without hitting the DB on
        // every dashboard render (cashiers tend to leave this tab open).
        $stats = Cache::remember('pos:dashboard:sales-today', now()->addSeconds(60), function (): array {
            $today = Order::query()->whereDate('order_date', now())->where('status', 'paid');

            return [
                'orders_today' => (clone $today)->count(),
                'gross_sales_today' => (float) (clone $today)->sum('grand_total'),
                'avg_basket' => (float) (clone $today)->avg('grand_total'),
                'pending_mpesa' => DB::table('payments')->where('method', 'mpesa')->where('status', 'pending')->count()
                    + DB::table('payments')->where('method', 'mpesa')->where('status', 'awaiting_confirmation')->count(),
            ];
        });

        return [
            Stat::make('Orders today', number_format($stats['orders_today']))
                ->icon('heroicon-o-shopping-cart')->color('primary'),
            Stat::make('Gross sales today', 'KES '.number_format($stats['gross_sales_today'], 2))
                ->icon('heroicon-o-banknotes')->color('success'),
            Stat::make('Average basket', 'KES '.number_format($stats['avg_basket'], 2))
                ->icon('heroicon-o-calculator')->color('gray'),
            Stat::make('Pending M-Pesa', number_format($stats['pending_mpesa']))
                ->icon('heroicon-o-clock')
                ->color($stats['pending_mpesa'] > 0 ? 'warning' : 'success'),
        ];
    }
}

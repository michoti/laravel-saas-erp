<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class TopProductsChartWidget extends ChartWidget
{
    protected ?string $heading = 'Top 10 products (last 30 days, by revenue)';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $rows = Cache::remember('pos:dashboard:top-products-30d', now()->addMinutes(15), function (): array {
            return DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.status', 'paid')
                ->where('orders.order_date', '>=', now()->subDays(30))
                ->selectRaw('order_items.product_name_snapshot AS product, SUM(order_items.line_total) AS revenue')
                ->groupBy('order_items.product_name_snapshot')
                ->orderByDesc('revenue')
                ->limit(10)
                ->pluck('revenue', 'product')
                ->all();
        });

        return [
            'datasets' => [[
                'label' => 'Revenue (KES)',
                'data' => array_values($rows),
                'backgroundColor' => '#6366f1',
            ]],
            'labels' => array_keys($rows),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

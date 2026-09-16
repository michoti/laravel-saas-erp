<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Reads from the pre-aggregated `pos_read_model_daily_sales_summary` table
 * (populated every 5 minutes by RefreshDashboardReadModelsCommand) instead
 * of summing the live `orders` table on every dashboard view — keeps this
 * chart fast regardless of how many historical orders a tenant has.
 */
final class SalesTrendChartWidget extends ChartWidget
{
    protected ?string $heading = 'Sales trend (last 30 days)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 2;

    protected function getData(): array
    {
        $rows = Cache::remember('pos:dashboard:sales-trend-30d', now()->addMinutes(5), function (): array {
            return DB::table('pos_read_model_daily_sales_summary')
                ->where('sales_date', '>=', now()->subDays(30)->toDateString())
                ->orderBy('sales_date')
                ->pluck('gross_sales', 'sales_date')
                ->all();
        });

        return [
            'datasets' => [[
                'label' => 'Gross sales (KES)',
                'data' => array_values($rows),
                'borderColor' => '#f59e0b',
                'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                'fill' => true,
            ]],
            'labels' => array_keys($rows),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

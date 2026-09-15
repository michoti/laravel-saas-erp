<?php

declare(strict_types=1);

namespace App\Filament\Central\Widgets;

use App\Models\Tenant;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

final class TenantGrowthChart extends ChartWidget
{
    protected ?string $heading = 'Tenant growth (last 12 months)';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $rows = Cache::tags(['central-dashboard'])->remember('central:tenant-growth-chart', now()->addHours(6), function (): array {
            return Tenant::query()
                ->selectRaw("to_char(created_at, 'YYYY-MM') AS month, COUNT(*) AS total")
                ->where('created_at', '>=', now()->subMonths(12)->startOfMonth())
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month')
                ->all();
        });

        return [
            'datasets' => [[
                'label' => 'New tenants',
                'data' => array_values($rows),
                'backgroundColor' => '#6366f1',
                'borderColor' => '#6366f1',
            ]],
            'labels' => array_keys($rows),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Central\Widgets;

use App\Models\TenantModule;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

final class ModuleAdoptionChart extends ChartWidget
{
    protected ?string $heading = 'Module adoption across tenants';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $rows = Cache::tags(['central-dashboard'])->remember('central:module-adoption-chart', now()->addHours(6), function (): array {
            return TenantModule::query()
                ->whereNotNull('enabled_at')
                ->selectRaw('module_key, COUNT(*) AS total')
                ->groupBy('module_key')
                ->pluck('total', 'module_key')
                ->all();
        });

        return [
            'datasets' => [[
                'label' => 'Tenants with module enabled',
                'data' => array_values($rows),
                'backgroundColor' => ['#6366f1', '#10b981', '#f59e0b', '#ef4444'],
            ]],
            'labels' => array_keys($rows),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

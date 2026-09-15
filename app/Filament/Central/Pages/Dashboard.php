<?php

declare(strict_types=1);

namespace App\Filament\Central\Pages;

use App\Filament\Central\Widgets\ModuleAdoptionChart;
use App\Filament\Central\Widgets\MrrTrendChart;
use App\Filament\Central\Widgets\TenantGrowthChart;
use App\Filament\Central\Widgets\TenantStatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;

final class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    public function getColumns(): int|array
    {
        return 2;
    }

    public function getWidgets(): array
    {
        return [
            TenantStatsOverview::class,
            TenantGrowthChart::class,
            MrrTrendChart::class,
            ModuleAdoptionChart::class,
        ];
    }
}

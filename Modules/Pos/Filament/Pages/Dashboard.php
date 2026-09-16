<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Modules\Pos\Filament\Widgets\PendingMpesaWidget;
use Modules\Pos\Filament\Widgets\SalesTodayStatsWidget;
use Modules\Pos\Filament\Widgets\SalesTrendChartWidget;
use Modules\Pos\Filament\Widgets\StockAlertsTableWidget;
use Modules\Pos\Filament\Widgets\TopProductsChartWidget;

final class Dashboard extends BaseDashboard
{
    protected static string| \BackedEnum|null $navigationIcon = 'heroicon-o-home';

    public function getColumns(): int|array
    {
        return 4;
    }

    public function getWidgets(): array
    {
        return [
            SalesTodayStatsWidget::class,
            SalesTrendChartWidget::class,
            TopProductsChartWidget::class,
            StockAlertsTableWidget::class,
            PendingMpesaWidget::class,
        ];
    }
}

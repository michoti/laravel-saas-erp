<?php

declare(strict_types=1);

namespace App\Filament\Central\Pages\Reports;

use App\Filament\Central\Pages\Concerns\PlatformReportPage;
use App\Reports\Platform\PlatformReportData;
use App\Reports\Platform\PlatformReportResult;

final class PlatformOverviewReport extends PlatformReportPage
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Platform overview';

    protected static ?string $slug = 'reports/platform-overview';

    protected static ?int $navigationSort = 1;

    protected function buildReport(): PlatformReportResult
    {
        return app(PlatformReportData::class)->platformOverview();
    }
}

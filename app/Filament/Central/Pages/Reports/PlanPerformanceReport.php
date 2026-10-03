<?php

declare(strict_types=1);

namespace App\Filament\Central\Pages\Reports;

use App\Filament\Central\Pages\Concerns\PlatformReportPage;
use App\Reports\Platform\PlatformReportData;
use App\Reports\Platform\PlatformReportResult;

final class PlanPerformanceReport extends PlatformReportPage
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Plan performance';

    protected static ?string $slug = 'reports/plan-performance';

    protected static ?int $navigationSort = 6;

    protected function buildReport(): PlatformReportResult
    {
        return app(PlatformReportData::class)->planPerformance();
    }
}

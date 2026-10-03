<?php

declare(strict_types=1);

namespace App\Filament\Central\Pages\Reports;

use App\Filament\Central\Pages\Concerns\PlatformReportPage;
use App\Reports\Platform\PlatformReportData;
use App\Reports\Platform\PlatformReportResult;

final class TrialActivityReport extends PlatformReportPage
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Trial activity';

    protected static ?string $slug = 'reports/trial-activity';

    protected static ?int $navigationSort = 5;

    protected function buildReport(): PlatformReportResult
    {
        return app(PlatformReportData::class)->trialActivity();
    }
}

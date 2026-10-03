<?php

declare(strict_types=1);

namespace App\Filament\Central\Pages\Reports;

use App\Filament\Central\Pages\Concerns\PlatformReportPage;
use App\Reports\Platform\PlatformReportData;
use App\Reports\Platform\PlatformReportResult;

final class ModuleAdoptionReport extends PlatformReportPage
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Module adoption';

    protected static ?string $slug = 'reports/module-adoption';

    protected static ?int $navigationSort = 8;

    protected function buildReport(): PlatformReportResult
    {
        return app(PlatformReportData::class)->moduleAdoption();
    }
}

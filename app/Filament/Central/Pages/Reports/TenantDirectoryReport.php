<?php

declare(strict_types=1);

namespace App\Filament\Central\Pages\Reports;

use App\Filament\Central\Pages\Concerns\PlatformReportPage;
use App\Reports\Platform\PlatformReportData;
use App\Reports\Platform\PlatformReportResult;

final class TenantDirectoryReport extends PlatformReportPage
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Tenant directory';

    protected static ?string $slug = 'reports/tenant-directory';

    protected static ?int $navigationSort = 3;

    protected function buildReport(): PlatformReportResult
    {
        return app(PlatformReportData::class)->tenantDirectory();
    }
}

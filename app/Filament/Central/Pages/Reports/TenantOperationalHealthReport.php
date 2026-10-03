<?php

declare(strict_types=1);

namespace App\Filament\Central\Pages\Reports;

use App\Filament\Central\Pages\Concerns\PlatformReportPage;
use App\Reports\Platform\PlatformReportData;
use App\Reports\Platform\PlatformReportResult;

final class TenantOperationalHealthReport extends PlatformReportPage
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Operational health';

    protected static ?string $slug = 'reports/tenant-operational-health';

    protected static ?int $navigationSort = 10;

    protected function buildReport(): PlatformReportResult
    {
        return app(PlatformReportData::class)->tenantOperationalHealth();
    }
}

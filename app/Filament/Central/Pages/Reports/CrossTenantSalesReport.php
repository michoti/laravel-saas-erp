<?php

declare(strict_types=1);

namespace App\Filament\Central\Pages\Reports;

use App\Filament\Central\Pages\Concerns\PlatformReportPage;
use App\Reports\Platform\PlatformReportData;
use App\Reports\Platform\PlatformReportResult;

final class CrossTenantSalesReport extends PlatformReportPage
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Cross-tenant sales';

    protected static ?string $slug = 'reports/cross-tenant-sales';

    protected static ?int $navigationSort = 9;

    protected function buildReport(): PlatformReportResult
    {
        return app(PlatformReportData::class)->crossTenantSales();
    }
}

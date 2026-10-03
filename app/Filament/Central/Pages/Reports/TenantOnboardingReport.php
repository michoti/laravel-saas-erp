<?php

declare(strict_types=1);

namespace App\Filament\Central\Pages\Reports;

use App\Filament\Central\Pages\Concerns\PlatformReportPage;
use App\Reports\Platform\PlatformReportData;
use App\Reports\Platform\PlatformReportResult;

final class TenantOnboardingReport extends PlatformReportPage
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?string $navigationLabel = 'Tenant onboarding';

    protected static ?string $slug = 'reports/tenant-onboarding';

    protected static ?int $navigationSort = 2;

    protected function buildReport(): PlatformReportResult
    {
        return app(PlatformReportData::class)->tenantOnboarding();
    }
}

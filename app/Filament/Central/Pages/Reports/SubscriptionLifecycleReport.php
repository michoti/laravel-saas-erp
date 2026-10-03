<?php

declare(strict_types=1);

namespace App\Filament\Central\Pages\Reports;

use App\Filament\Central\Pages\Concerns\PlatformReportPage;
use App\Reports\Platform\PlatformReportData;
use App\Reports\Platform\PlatformReportResult;

final class SubscriptionLifecycleReport extends PlatformReportPage
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationLabel = 'Subscription lifecycle';

    protected static ?string $slug = 'reports/subscription-lifecycle';

    protected static ?int $navigationSort = 4;

    protected function buildReport(): PlatformReportResult
    {
        return app(PlatformReportData::class)->subscriptionLifecycle();
    }
}

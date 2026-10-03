<?php

declare(strict_types=1);

namespace App\Filament\Central\Pages\Reports;

use App\Filament\Central\Pages\Concerns\PlatformReportPage;
use App\Reports\Platform\PlatformReportData;
use App\Reports\Platform\PlatformReportResult;

final class InvoiceCollectionReport extends PlatformReportPage
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $navigationLabel = 'Invoice collection';

    protected static ?string $slug = 'reports/invoice-collection';

    protected static ?int $navigationSort = 7;

    protected function buildReport(): PlatformReportResult
    {
        return app(PlatformReportData::class)->invoiceCollection();
    }
}

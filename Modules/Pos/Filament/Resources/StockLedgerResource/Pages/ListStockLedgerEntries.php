<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Resources\StockLedgerResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Pos\Filament\Resources\StockLedgerResource;

final class ListStockLedgerEntries extends ListRecords
{
    protected static string $resource = StockLedgerResource::class;
}

<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Resources\OrderResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Pos\Filament\Resources\OrderResource;

final class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
}

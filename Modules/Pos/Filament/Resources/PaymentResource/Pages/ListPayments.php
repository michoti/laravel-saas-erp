<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Resources\PaymentResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Pos\Filament\Resources\PaymentResource;

final class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;
}

<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Resources\CustomerResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Pos\Filament\Resources\CustomerResource;

final class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
}

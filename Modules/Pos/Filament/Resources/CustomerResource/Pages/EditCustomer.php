<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Resources\CustomerResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Pos\Filament\Resources\CustomerResource;

final class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->color('danger')];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Resources\CustomerResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Pos\Filament\Resources\CustomerResource;

final class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        // Slide-over rather than a modal: the customer form is small today
        // but this keeps the create flow consistent with the rest of the
        // panel's "complex form => drawer" convention (see RefundAction).
        return [CreateAction::make()->slideOver()];
    }
}

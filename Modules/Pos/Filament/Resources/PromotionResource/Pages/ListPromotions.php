<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Resources\PromotionResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Pos\Filament\Resources\PromotionResource;

final class ListPromotions extends ListRecords
{
    protected static string $resource = PromotionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->slideOver()];
    }
}

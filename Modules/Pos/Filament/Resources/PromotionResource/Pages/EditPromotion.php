<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Resources\PromotionResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Pos\Filament\Resources\PromotionResource;

final class EditPromotion extends EditRecord
{
    protected static string $resource = PromotionResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->color('danger')];
    }
}

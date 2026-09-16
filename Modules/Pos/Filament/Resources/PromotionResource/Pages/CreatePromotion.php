<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Resources\PromotionResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Pos\Filament\Resources\PromotionResource;

final class CreatePromotion extends CreateRecord
{
    protected static string $resource = PromotionResource::class;
}

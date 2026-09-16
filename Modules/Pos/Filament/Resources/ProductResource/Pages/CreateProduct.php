<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Resources\ProductResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Pos\Filament\Resources\ProductResource;

final class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}

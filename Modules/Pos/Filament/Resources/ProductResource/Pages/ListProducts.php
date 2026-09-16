<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Resources\ProductResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Pos\Filament\Imports\ProductsImporter;
use Modules\Pos\Filament\Resources\ProductResource;

final class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Filament's import action is queued internally by default
            // (one job per chunk of rows) — a 10k-row catalog CSV never
            // blocks the request thread. See ProductsImporter.
            ImportAction::make()->importer(ProductsImporter::class),
            CreateAction::make(),
        ];
    }
}

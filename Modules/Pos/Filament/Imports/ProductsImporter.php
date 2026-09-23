<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Imports;

use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Modules\Pos\App\Models\Product;

/**
 * Filament's import system is queued natively (each chunk of rows is
 * processed as its own queued job — see ->queue('exports') on the
 * ImportAction in ProductResource) — a 10,000-row catalog CSV never runs
 * on the web request thread.
 */
final class ProductsImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('sku')->requiredMapping()->rules(['required', 'max:64']),
            ImportColumn::make('barcode')->rules(['nullable', 'max:64']),
            ImportColumn::make('name')->requiredMapping()->rules(['required', 'max:255']),
            ImportColumn::make('category')->rules(['nullable', 'max:255']),
            ImportColumn::make('unit_price')->requiredMapping()->numeric()->rules(['required', 'numeric', 'min:0']),
            ImportColumn::make('tax_rate')->numeric()->rules(['nullable', 'numeric', 'min:0', 'max:1']),
        ];
    }

    public function resolveRecord(): Product
    {
        return Product::query()->firstOrNew(['sku' => $this->data['sku']]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $failed = $import->getFailedRowsCount();

        return "Imported {$import->successful_rows} product(s)."
            .($failed > 0 ? " {$failed} row(s) failed — download the error CSV for details." : '');
    }
}

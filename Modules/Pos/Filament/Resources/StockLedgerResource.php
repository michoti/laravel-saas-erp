<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Pos\Filament\Resources\StockLedgerResource\Pages;
use Modules\Pos\App\Models\StockLedgerEntry;

final class StockLedgerResource extends Resource
{
    protected static ?string $model = StockLedgerEntry::class;

    protected static string| \BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Stock ledger';

    protected static string|\UnitEnum|null $navigationGroup = 'Point of Sale';

    protected static ?int $navigationSort = 4;

    // Append-only at the DB level (see the Postgres trigger in its
    // migration) — no policy grants create/update/delete either, so this
    // resource is intentionally read-only end to end.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('product:id,name,sku');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')->label('Product')->searchable(),
                TextColumn::make('movement_type')->badge(),
                TextColumn::make('quantity_delta')
                    ->label('Qty change')
                    ->color(fn ($state): string => (float) $state < 0 ? 'danger' : 'success'),
                TextColumn::make('occurred_at')->dateTime()->sortable(),
                TextColumn::make('reference_type')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('movement_type')->options([
                    'sale' => 'Sale', 'return' => 'Return', 'purchase' => 'Purchase',
                    'adjustment' => 'Adjustment', 'transfer_in' => 'Transfer in', 'transfer_out' => 'Transfer out',
                ]),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListStockLedgerEntries::route('/')];
    }
}

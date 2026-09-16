<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Modules\Pos\Models\Product;

final class StockAlertsTableWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Low stock alerts (< 10 units)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Same withSum trick as ProductResource — one query for the
                // whole widget, `having()` filters in SQL rather than in PHP.
                Product::query()
                    ->where('is_active', true)
                    ->where('track_inventory', true)
                    ->withSum('stockLedgerEntries as stock_on_hand', 'quantity_delta')
                    ->having('stock_on_hand', '<', 10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('sku'),
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('stock_on_hand')->label('On hand')->color('danger')->weight('bold'),
            ])
            ->paginated([5, 10, 25])
            ->poll('300s');
    }
}

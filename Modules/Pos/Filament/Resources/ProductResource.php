<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Resources;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Modules\Pos\Filament\Imports\ProductsImporter;
use Modules\Pos\Filament\Resources\ProductResource\Pages;
use Modules\Pos\Models\Product;

final class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string| \BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|\UnitEnum|null $navigationGroup = 'Point of Sale';

    protected static ?int $navigationSort = 1;

    /**
     * Sidebar badges render on EVERY navigation render (i.e. every page
     * load in the panel) — without caching this would run a SUM query on
     * every single click. 5-minute TTL keeps it fresh enough for a retail
     * floor while eliminating that cost almost entirely.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = Cache::remember('pos:nav:low-stock-count', now()->addMinutes(5), fn () => Product::query()
            ->where('is_active', true)
            ->where('track_inventory', true)
            ->withSum('stockLedgerEntries as stock_on_hand', 'quantity_delta')
            ->get()
            ->filter(fn (Product $p): bool => (float) $p->stock_on_hand < 10)
            ->count());

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    /**
     * Stock-on-hand is computed with a single correlated SUM subquery
     * (withSum) rather than calling Product::stockOnHand() per row —
     * the latter would issue one query per visible row (classic N+1).
     * This scales to a full page of 50 products in one query, always.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withSum('stockLedgerEntries as stock_on_hand', 'quantity_delta');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    \Filament\Forms\Components\FileUpload::make('image_path')
                        ->label('Product photo')
                        ->image()
                        ->directory('product-images')
                        ->imageEditor()
                        ->columnSpanFull(),
                    TextInput::make('sku')->required()->unique(ignoreRecord: true)->maxLength(64),
                    TextInput::make('barcode')
                        ->label('Barcode')
                        ->unique(ignoreRecord: true)
                        ->maxLength(64)
                        ->helperText('Scan a barcode into this field once, at setup time, to link it for checkout.'),
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('category')->maxLength(255)->datalist(fn () => Product::query()->distinct()->pluck('category')->filter()->all()),
                    TextInput::make('unit_price')->numeric()->prefix('KES')->required(),
                    TextInput::make('tax_rate')->numeric()->suffix('%')->step(0.01)->required()
                        ->dehydrateStateUsing(fn ($state) => $state / 100)
                        ->formatStateUsing(fn ($state) => $state ? $state * 100 : null),
                    Toggle::make('is_active')->default(true),
                    Toggle::make('track_inventory')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\ImageColumn::make('image_path')->label('')->circular()->size(40),
                TextColumn::make('sku')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('unit_price')->money('KES')->sortable(),
                TextColumn::make('stock_on_hand')
                    ->label('Stock on hand')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state): string => (float) $state < 10 ? 'danger' : 'success')
                    ->weight(fn ($state): string => (float) $state < 10 ? 'bold' : 'normal'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
                Filter::make('low_stock')
                    ->label('Low stock (< 10)')
                    ->query(fn (Builder $query): Builder => $query->having('stock_on_hand', '<', 10)),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make()->color('warning'),
                \Filament\Actions\DeleteAction::make()->color('danger'),
            ])
            ->defaultSort('name')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}

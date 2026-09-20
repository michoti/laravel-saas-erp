<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Pos\Filament\Resources\PromotionResource\Pages;
use Modules\Pos\App\Models\Product;
use Modules\Pos\App\Models\Promotion;

/**
 * Automatic promotional pricing at the till: once created here, a
 * promotion applies itself the moment it's active — Product::
 * activePromotion() (consulted by the POS Terminal when a product is
 * added to the cart) evaluates is_active + the starts_at/ends_at window on
 * every read, so a scheduled sale starts and ends exactly on time with no
 * cron job or "flip the switch" step for staff to remember.
 *
 * Offline devices do NOT go through this path: they receive the current
 * (promotional, if any) unit_price via the reference-data LWW sync
 * described in ARCHITECTURE.md §3 and submit that price with the order,
 * so ProcessSyncBatchJob trusts the client's unit_price rather than
 * re-evaluating promotions server-side a second time.
 */
final class PromotionResource extends Resource
{
    protected static ?string $model = Promotion::class;

    protected static string| \BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'Point of Sale';

    protected static ?int $navigationSort = 6;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('product:id,name');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    Select::make('product_id')
                        ->label('Applies to')
                        ->options(fn () => Product::query()->pluck('name', 'id'))
                        ->searchable()
                        ->placeholder('Storewide (all products)'),
                    Select::make('discount_type')->options([
                        'percentage' => 'Percentage off',
                        'fixed_amount' => 'Fixed amount off',
                    ])->required()->native(false)->live(),
                    TextInput::make('discount_value')
                        ->numeric()
                        ->required()
                        ->suffix(fn (callable $get): string => $get('discount_type') === 'percentage' ? '%' : 'KES'),
                    DateTimePicker::make('starts_at')->required()->native(false),
                    DateTimePicker::make('ends_at')->required()->native(false)->after('starts_at'),
                    Toggle::make('is_active')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('product.name')->label('Scope')->placeholder('Storewide'),
                TextColumn::make('discount_value')
                    ->formatStateUsing(fn ($state, $record): string => $record->discount_type === \App\Enums\PromotionDiscountType::Percentage ? "{$state}%" : "KES {$state}"),
                TextColumn::make('starts_at')->dateTime()->sortable(),
                TextColumn::make('ends_at')->dateTime()->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make()->color('warning'),
                \Filament\Actions\DeleteAction::make()->color('danger'),
            ])
            ->defaultSort('starts_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromotions::route('/'),
            'create' => Pages\CreatePromotion::route('/create'),
            'edit' => Pages\EditPromotion::route('/{record}/edit'),
        ];
    }
}

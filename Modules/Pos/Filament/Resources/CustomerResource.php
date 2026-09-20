<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Resources;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Pos\Filament\Resources\CustomerResource\Pages;
use Modules\Pos\App\Models\Customer;

final class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string| \BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|\UnitEnum|null $navigationGroup = 'Point of Sale';

    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('orders');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('phone')->tel()->maxLength(32),
                TextInput::make('email')->email()->maxLength(255),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('phone')->searchable(),
                TextColumn::make('email')->searchable()->toggleable(),
                TextColumn::make('store_credit_balance')->money('KES')->sortable(),
                TextColumn::make('orders_count')->label('Orders'),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make()->color('warning'),
                \Filament\Actions\DeleteAction::make()->color('danger'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}

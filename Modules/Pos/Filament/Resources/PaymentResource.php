<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Pos\Filament\Resources\PaymentResource\Pages;
use Modules\Pos\App\Models\Payment;

final class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string| \BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Point of Sale';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false; // payments only originate from ProcessSyncBatchJob / M-Pesa callback
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('order:id,invoice_number');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.invoice_number')->label('Invoice')->searchable(),
                TextColumn::make('method')->badge(),
                TextColumn::make('status')->badge()->color(fn (\App\Enums\PaymentStatus $state): string => $state->color()),
                TextColumn::make('amount')->money('KES')->sortable(),
                TextColumn::make('mpesa_receipt_number')->label('M-Pesa receipt')->toggleable(),
                TextColumn::make('paid_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('method')->options(['cash' => 'Cash', 'mpesa' => 'M-Pesa', 'card' => 'Card']),
                SelectFilter::make('status')->options([
                    'pending' => 'Pending', 'awaiting_confirmation' => 'Awaiting confirmation',
                    'completed' => 'Completed', 'failed' => 'Failed',
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListPayments::route('/')];
    }
}

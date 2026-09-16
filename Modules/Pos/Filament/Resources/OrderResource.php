<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Resources;

use App\Jobs\GenerateOrdersExportJob;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Pos\Filament\Resources\OrderResource\Pages;
use Modules\Pos\Models\Order;
use Modules\Pos\Services\RefundService;

final class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string| \BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string|\UnitEnum|null $navigationGroup = 'Point of Sale';

    protected static ?int $navigationSort = 2;

    // Orders are sync-derived; back office only ever reads them here, so
    // the whole resource is view-only (create/edit/delete all disabled).
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Eager-load line items + their product + payments in ONE extra pair
     * of queries for the whole page, instead of N+1-ing per order row when
     * the table or the view page renders item/payment counts and sums.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['items.product:id,name,sku', 'payments:id,order_id,method,status,amount'])
            ->withCount('items');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]); // read-only resource, no create/edit form
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')->searchable()->sortable(),
                TextColumn::make('local_reference')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('items_count')->label('Items'),
                TextColumn::make('grand_total')->money('KES')->sortable(),
                TextColumn::make('status')->badge()->color(fn (\App\Enums\OrderStatus $state): string => $state->color()),
                TextColumn::make('payments.method')->badge()->label('Payment method'),
                TextColumn::make('order_date')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'awaiting_payment' => 'Awaiting payment',
                    'paid' => 'Paid',
                    'refunded' => 'Refunded',
                    'voided' => 'Voided',
                ]),
            ])
            ->recordActions([
                Action::make('refund')
                    ->label('Process return')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    // Slide-over rather than a modal — the refund form has
                    // several interdependent fields (item, method, amount,
                    // reason) that need room to breathe, per the panel's
                    // "complex form => drawer" convention.
                    ->slideOver()
                    ->visible(fn (Order $record): bool => auth()->user()->can('process_refund')
                        && in_array($record->status, [\App\Enums\OrderStatus::Paid, \App\Enums\OrderStatus::PartiallyRefunded], true))
                    ->schema([
                        Select::make('order_item_id')
                            ->label('Item (leave blank for a whole-order refund)')
                            ->options(fn (Order $record) => $record->items->pluck('product_name_snapshot', 'id'))
                            ->native(false),
                        Radio::make('method')
                            ->options(['cash' => 'Cash', 'mpesa_manual' => 'M-Pesa (manual reversal)', 'store_credit' => 'Store credit'])
                            ->required()
                            ->inline(),
                        TextInput::make('amount')->numeric()->prefix('KES')->required(),
                        Textarea::make('reason')->rows(2),
                    ])
                    ->action(function (Order $record, array $data, RefundService $refundService): void {
                        try {
                            $refundService->process(
                                order: $record,
                                amount: (float) $data['amount'],
                                method: \App\Enums\RefundMethod::from($data['method']),
                                processedByUserId: auth()->id(),
                                orderItem: $data['order_item_id'] ? $record->items->find($data['order_item_id']) : null,
                                reason: $data['reason'] ?? null,
                            );

                            \Filament\Notifications\Notification::make()->title('Refund processed')->success()->send();
                        } catch (\RuntimeException $e) {
                            \Filament\Notifications\Notification::make()->title('Refund failed')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->headerActions([
                // Heavy export work never runs inline on the request —
                // dispatched to the `exports` queue and the user is
                // notified (Filament database notification) when the
                // download is ready. See App\Jobs\GenerateOrdersExportJob.
                Action::make('export')
                    ->label('Export to Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        GenerateOrdersExportJob::dispatch(auth()->id(), tenant('id'))->onQueue('exports');

                        \Filament\Notifications\Notification::make()
                            ->title('Export queued')
                            ->body('We\'ll notify you here once your orders export is ready to download.')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('order_date', 'desc')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}

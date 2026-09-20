<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Modules\Pos\App\Models\Payment;

/**
 * Surfaces M-Pesa payments still awaiting the Daraja callback so staff can
 * see what's "stuck" without digging through the payments list. Polls
 * every 15s — this table is small (pending payments only) so it's cheap
 * even at that frequency, and Reverb's `payment.confirmed` broadcast (see
 * MpesaCallbackController) means most rows clear themselves in real time
 * anyway; the poll is just a fallback for devices not currently connected.
 */
final class PendingMpesaWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Pending M-Pesa confirmations';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()
                    ->with('order:id,invoice_number,local_reference')
                    ->where('method', 'mpesa')
                    ->whereIn('status', ['pending', 'awaiting_confirmation'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('order.local_reference')->label('Order'),
                Tables\Columns\TextColumn::make('payer_phone'),
                Tables\Columns\TextColumn::make('amount')->money('KES'),
                Tables\Columns\TextColumn::make('status')->badge()->color('warning'),
                Tables\Columns\TextColumn::make('created_at')->since()->label('Initiated'),
            ])
            ->poll('15s')
            ->paginated([5, 10]);
    }
}

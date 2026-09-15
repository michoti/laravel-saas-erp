<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\TenantResource\RelationManagers;

use App\Enums\SubscriptionInvoiceStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Read-only billing history for a tenant — invoices are only ever
 * created/resolved by App\Services\Billing\SubscriptionService and the
 * M-Pesa resolver, never edited by hand here.
 */
final class SubscriptionInvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('due_date')->dateTime()->sortable(),
                TextColumn::make('amount')->money('KES')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (SubscriptionInvoiceStatus $state): string => $state->color()),
                TextColumn::make('mpesa_receipt_number')->label('M-Pesa receipt')->toggleable(),
                TextColumn::make('paid_at')->dateTime()->toggleable(),
            ])
            ->defaultSort('due_date', 'desc');
    }
}

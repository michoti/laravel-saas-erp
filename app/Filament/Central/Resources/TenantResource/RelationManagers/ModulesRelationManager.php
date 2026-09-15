<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\TenantResource\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

/**
 * Lets a superadmin toggle which business-domain modules (POS, Invoicing,
 * CRM...) a tenant has purchased — this is what EnsureModuleIsEnabled
 * checks on every tenant API/Filament request.
 */
final class ModulesRelationManager extends RelationManager
{
    protected static string $relationship = 'modules';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('module_key')
                ->options([
                    'pos' => 'Point of Sale',
                    'invoicing' => 'Invoicing',
                    'crm' => 'CRM',
                    'inventory' => 'Inventory',
                ])
                ->required()
                ->native(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('module_key')
            ->columns([
                TextColumn::make('module_key')->badge(),
                IconColumn::make('enabled_at')->label('Active')->boolean()
                    ->getStateUsing(fn ($record): bool => $record->enabled_at !== null),
                TextColumn::make('enabled_at')->dateTime()->toggleable(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                Action::make('toggle')
                    ->label(fn ($record): string => $record->enabled_at ? 'Disable' : 'Enable')
                    ->icon(fn ($record): string => $record->enabled_at ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record): string => $record->enabled_at ? 'danger' : 'success')
                    ->action(function ($record): void {
                        $record->update([
                            'enabled_at' => $record->enabled_at ? null : now(),
                            'disabled_at' => $record->enabled_at ? now() : null,
                        ]);

                        // The tenant's module list is cached (see
                        // EnsureModuleIsEnabled) — bust it immediately so
                        // the change takes effect on the tenant's very next
                        // request instead of waiting out the TTL.
                        Cache::forget("tenant:{$record->tenant_id}:modules");
                    }),
                DeleteAction::make(),
            ]);
    }
}

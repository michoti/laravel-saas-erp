<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\TenantResource\RelationManagers;

use App\Models\TenantModule;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

/**
 * Lets a superadmin toggle which business modules a tenant has purchased.
 * Tenant::hasModuleEnabled() reads this table directly (memoized per
 * request), so a toggle takes effect on the tenant's very next request
 * with no cache to bust.
 */
final class ModulesRelationManager extends RelationManager
{
    protected static string $relationship = 'modules';

    private const MODULES = [
        'pos' => 'Point of Sale',
        'invoicing' => 'Invoicing',
        'crm' => 'CRM',
        'inventory' => 'Inventory',
    ];

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('module_key')
                ->options(self::MODULES)
                ->required()
                ->native(false)
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule): Unique => $rule
                        ->where('tenant_id', $this->getOwnerRecord()->getKey()),
                ),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('module_key')
            ->columns([
                TextColumn::make('module_key')
                    ->label('Module')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::MODULES[$state] ?? $state),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->state(fn (TenantModule $record): bool => $record->enabled_at !== null),
                TextColumn::make('enabled_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(fn (array $data): array => [...$data, 'enabled_at' => now()]),
            ])
            ->recordActions([
                Action::make('toggle')
                    ->label(fn (TenantModule $record): string => $record->enabled_at ? 'Disable' : 'Enable')
                    ->icon(fn (TenantModule $record): string => $record->enabled_at ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (TenantModule $record): string => $record->enabled_at ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (TenantModule $record): void {
                        $enabling = $record->enabled_at === null;

                        $record->update([
                            'enabled_at' => $enabling ? now() : null,
                            'disabled_at' => $enabling ? null : now(),
                        ]);

                        Notification::make()
                            ->title($enabling ? 'Module enabled' : 'Module disabled')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
            ])
            ->paginated(false);
    }
}

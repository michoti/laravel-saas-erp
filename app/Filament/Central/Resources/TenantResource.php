<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources;

use App\Filament\Central\Resources\TenantResource\Pages;
use App\Filament\Central\Resources\TenantResource\RelationManagers\ModulesRelationManager;
use App\Filament\Central\Resources\TenantResource\RelationManagers\SubscriptionInvoicesRelationManager;
use App\Models\Tenant;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 1;

    // Every list view eager-loads domains + modules + the subscription
    // (and its plan) in one round trip instead of N+1-ing per row — see
    // the `domains_count`/`modules_count` columns below, which use
    // withCount rather than looping relations, and `subscription.plan`
    // below, which is a single extra query for the whole page rather
    // than one per row.
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['domains', 'modules'])
            ->with(['domains' => fn ($query) => $query->limit(1), 'subscription.plan']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Tenant details')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('billing_phone')
                        ->label('Billing phone (M-Pesa)')
                        ->tel()
                        ->helperText('Where subscription renewal STK Push prompts are sent.'),
                ]),

            Section::make('Branding (injected into the tenant Filament panel at runtime)')
                ->columns(2)
                ->schema([
                    ColorPicker::make('theme.primary_color')->label('Primary color'),
                    TextInput::make('theme.font_family')->label('Font family'),
                    FileUpload::make('theme.logo_url')->label('Logo')->image()->directory('tenant-logos'),
                    TextInput::make('theme.custom_css')->label('Custom CSS override')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('domains.domain')->label('Domain')->badge(),
                TextColumn::make('subscription.plan.name')->label('Plan')->badge()->placeholder('No subscription'),
                TextColumn::make('subscription.status')
                    ->label('Billing status')
                    ->badge()
                    ->color(fn (?\App\Enums\SubscriptionStatus $state): string => $state?->color() ?? 'gray')
                    ->formatStateUsing(fn (?\App\Enums\SubscriptionStatus $state): string => $state?->label() ?? '—'),
                TextColumn::make('modules_count')->label('Modules enabled')->sortable(),
                TextColumn::make('subscription.current_period_end')->label('Renews')->date()->sortable()->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->defaultSort('created_at', 'desc')
            // Keep pagination bounded — never let an admin accidentally
            // pull every tenant row (and its withCount joins) in one page.
            ->paginated([10, 25, 50]);
    }

    public static function getRelations(): array
    {
        return [ModulesRelationManager::class, SubscriptionInvoicesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}

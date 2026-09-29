<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources;

use App\Enums\SubscriptionStatus;
use App\Filament\Central\Resources\TenantResource\Pages;
use App\Filament\Central\Resources\TenantResource\RelationManagers\ModulesRelationManager;
use App\Filament\Central\Resources\TenantResource\RelationManagers\SubscriptionInvoicesRelationManager;
use App\Models\Plan;
use App\Models\Tenant;
use BackedEnum;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use UnitEnum;

final class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|UnitEnum|null $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 1;

    /**
     * List queries: one COUNT subquery for modules, plus one extra query
     * each for the primary domain and subscription -> plan (no N+1).
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('modules')
            ->with(['primaryDomain', 'subscription.plan']);
    }

    /**
     * Active plans only, in the configured display order. On edit, the
     * tenant's current plan is always included even if it was retired, so
     * the select never renders blank or fails validation.
     *
     * @return array<int, string>
     */
    private static function planOptions(int|string|null $currentPlanId = null): array
    {
        return Plan::query()
            ->where('is_active', true)
            ->when($currentPlanId, fn (Builder $query): Builder => $query->orWhere('id', $currentPlanId))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Tenant details')
                ->columns(2)
                ->components([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('billing_phone')
                        ->label('Billing phone (M-Pesa)')
                        ->tel()
                        ->maxLength(20)
                        ->helperText('Where subscription renewal STK Push prompts are sent.'),
                    TextInput::make('domain')
                        ->label('Domain')
                        ->helperText('Full hostname, e.g. acme.example.com')
                        ->required()
                        ->maxLength(253)
                        ->regex('/^(?!-)[A-Za-z0-9-]{1,63}(\.[A-Za-z0-9-]{1,63})+$/')
                        ->dehydrateStateUsing(fn (?string $state): string => Str::lower(mb_trim((string) $state)))
                        ->rules([
                            Rule::unique(
                                config('tenancy.database.central_connection', 'central') . '.domains',
                                'domain',
                            ),
                        ])
                        ->visibleOn('create'),
                    Select::make('plan_id')
                        ->label('Plan')
                        ->options(fn (?Model $record): array => self::planOptions($record?->subscription?->plan_id))
                        ->required()
                        ->searchable()
                        ->native(false),
                ]),

            Section::make('Branding (injected into the tenant Filament panel at runtime)')
                ->columns(2)
                ->components([
                    ColorPicker::make('theme.primary_color')
                        ->label('Primary color'),
                    TextInput::make('theme.font_family')
                        ->label('Font family')
                        ->maxLength(100)
                        ->regex('/^[\w\s,\'"-]+$/'),
                    FileUpload::make('theme.logo_url')
                        ->label('Logo')
                        ->image()
                        ->disk('public')
                        ->visibility('public')
                        ->directory('tenant-logos'),
                    Textarea::make('theme.custom_css')
                        ->label('Custom CSS override')
                        ->rows(6)
                        ->maxLength(10000)
                        ->rules([
                            fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                if (is_string($value) && preg_match('/<\s*\/?\s*(style|script)/i', $value) === 1) {
                                    $fail('Custom CSS must not contain <style> or <script> tags.');
                                }
                            },
                        ])
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('primaryDomain.domain')
                    ->label('Domain')
                    ->badge()
                    ->searchable(),
                TextColumn::make('subscription.plan.name')
                    ->label('Plan')
                    ->badge()
                    ->placeholder('No subscription')
                    ->searchable(),
                TextColumn::make('subscription.status')
                    ->label('Billing status')
                    ->badge()
                    ->color(fn (?SubscriptionStatus $state): string => $state?->color() ?? 'gray')
                    ->formatStateUsing(fn (?SubscriptionStatus $state): string => $state?->label() ?? '—'),
                TextColumn::make('modules_count')
                    ->label('Modules enabled')
                    ->sortable(),
                TextColumn::make('subscription.current_period_end')
                    ->label('Renews')
                    ->date()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->options(fn (): array => Plan::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('subscription', fn (Builder $q): Builder => $q->where('plan_id', $data['value']))
                        : $query),
                SelectFilter::make('billing_status')
                    ->label('Billing status')
                    ->options(fn (): array => collect(SubscriptionStatus::cases())
                        ->mapWithKeys(fn (SubscriptionStatus $case): array => [$case->value => $case->label()])
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('subscription', fn (Builder $q): Builder => $q->where('status', $data['value']))
                        : $query),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            // Bounded pagination: never pull every tenant in one page.
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(25)
            ->deferLoading()
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            ModulesRelationManager::class,
            SubscriptionInvoicesRelationManager::class,
        ];
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

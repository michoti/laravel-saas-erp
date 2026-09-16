<?php

declare(strict_types=1);

namespace Modules\Pos\Filament\Pages;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Modules\Pos\Models\TenantMpesaSetting;

/**
 * Where a store owner configures THEIR OWN M-Pesa Till/PayBill —
 * deliberately a single-record settings page, not a Resource, since
 * there is exactly one of these per tenant database (see
 * TenantMpesaSetting::current()). Restricted to the Owner role: these
 * are the credentials that receive the tenant's own customers' money,
 * not something a cashier or even a manager should be able to change.
 */
final class MpesaSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string| \BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'M-Pesa Settings';

    protected string $view = 'pos::filament.pages.mpesa-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('Owner') ?? false;
    }

    public function mount(): void
    {
        $settings = TenantMpesaSetting::current();

        $this->form->fill([
            'consumer_key' => $settings?->consumer_key,
            // Secrets are never pre-filled into the form — the fields
            // stay blank on load; submitting the form with them blank
            // leaves the previously-saved encrypted value untouched
            // rather than overwriting it with an empty string (see
            // dehydrated() below).
            'consumer_secret' => null,
            'shortcode' => $settings?->shortcode,
            'passkey' => null,
            'env' => $settings?->env ?? 'sandbox',
            'transaction_type' => $settings?->transaction_type ?? 'paybill',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Your M-Pesa (Daraja) credentials')
                    ->description(
                        'Customer payments taken via the POS Terminal\'s M-Pesa option are '
                        .'sent directly to YOUR Till/PayBill using these credentials — the '
                        .'platform never sees or touches this money. Stored encrypted, and '
                        .'never shown again once saved.'
                    )
                    ->columns(2)
                    ->schema([
                        TextInput::make('consumer_key')->required()->password()->revealable(),
                        TextInput::make('consumer_secret')
                            ->password()->revealable()
                            ->required(fn (): bool => ! TenantMpesaSetting::current()?->isConfigured())
                            ->dehydrated(fn ($state): bool => filled($state)),
                        TextInput::make('shortcode')->required()->label('Till / PayBill number'),
                        TextInput::make('passkey')
                            ->password()->revealable()
                            ->required(fn (): bool => ! TenantMpesaSetting::current()?->isConfigured())
                            ->dehydrated(fn ($state): bool => filled($state)),
                        Select::make('transaction_type')->options(['paybill' => 'PayBill', 'till' => 'Till (Buy Goods)'])->required()->native(false),
                        Select::make('env')->options(['sandbox' => 'Sandbox (testing)', 'production' => 'Production'])->required()->native(false),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        TenantMpesaSetting::query()->firstOrCreate([])->update(array_filter([
            'consumer_key' => $data['consumer_key'],
            'consumer_secret' => $data['consumer_secret'] ?? null,
            'shortcode' => $data['shortcode'],
            'passkey' => $data['passkey'] ?? null,
            'env' => $data['env'],
            'transaction_type' => $data['transaction_type'],
        ], fn ($value): bool => $value !== null));

        Notification::make()->title('M-Pesa settings saved')->success()->send();
    }
}

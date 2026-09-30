<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\TenantResource\Pages;

use App\Actions\Tenants\AssignTenantPlan;
use App\Actions\Tenants\ProvisionTenantOwnerUser;
use App\Filament\Central\Resources\TenantResource;
use App\Models\Tenant;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // stancl/tenancy uses the id as the tenant DB suffix; generate it
        // up front so the form never needs to expose it.
        $data['id'] = (string) Str::uuid();

        return $data;
    }

    /**
     * `domain`, `plan_id` and `owner_email` are not tenant columns, so they
     * are split off and handled explicitly.
     *
     * The tenant is created OUTSIDE the transaction on purpose:
     * Tenant::created dispatches the CreateDatabase/MigrateDatabase
     * pipeline (see TenancyServiceProvider), which needs a committed row to
     * see. The domain and subscription are then written atomically; if
     * that fails, the tenant is removed again so no domain-less orphan is
     * left behind. The owner user is provisioned last, since it depends on
     * the tenant database existing — see ProvisionTenantOwnerUser's
     * docblock for what happens if that database isn't ready yet.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $planId = Arr::pull($data, 'plan_id');
        $domain = Arr::pull($data, 'domain');
        $ownerEmail = Arr::pull($data, 'owner_email');

        /** @var Tenant $tenant */
        $tenant = Tenant::create($data);

        try {
            DB::connection(config('tenancy.database.central_connection', 'central'))
                ->transaction(function () use ($tenant, $domain, $planId): void {
                    $tenant->domains()->create(['domain' => $domain]);
                    app(AssignTenantPlan::class)->handle($tenant, $planId);
                });
        } catch (Throwable $e) {
            $tenant->delete();

            throw $e;
        }

        $this->provisionOwnerOrWarn($tenant, $ownerEmail);

        return $tenant;
    }

    private function provisionOwnerOrWarn(Tenant $tenant, string $ownerEmail): void
    {
        try {
            $credentials = app(ProvisionTenantOwnerUser::class)->handle($tenant, $ownerEmail);

            Notification::make()
                ->title('Owner account created')
                ->success()
                ->body(
                    "Email: {$credentials['email']}\n" .
                    "Temporary password: {$credentials['password']}\n\n" .
                    'Share this with the tenant now — it will not be shown again.',
                )
                ->persistent()
                ->send();
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title('Tenant created, but the owner login could not be provisioned yet')
                ->warning()
                ->body(
                    "This usually means the tenant database wasn't finished provisioning yet. Once it is, run:\n" .
                    "php artisan tenants:provision-owner {$tenant->getKey()} {$ownerEmail}",
                )
                ->persistent()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\TenantResource\Pages;

use App\Actions\Tenants\AssignTenantPlan;
use App\Filament\Central\Resources\TenantResource;
use App\Models\Tenant;
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
     * `domain` and `plan_id` are not tenant columns, so they are split off
     * and handled explicitly.
     *
     * The tenant is created OUTSIDE a transaction on purpose: Tenant::created
     * dispatches the queued CreateDatabase/MigrateDatabase pipeline, and that
     * job must be able to see a committed row. The domain and subscription
     * are then written atomically; if that fails, the tenant is removed
     * again so no domain-less orphan is left behind.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $planId = Arr::pull($data, 'plan_id');
        $domain = Arr::pull($data, 'domain');

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

        return $tenant;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}

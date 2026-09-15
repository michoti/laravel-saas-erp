<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\TenantResource\Pages;

use App\Filament\Central\Resources\TenantResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // stancl/tenancy uses the id as the tenant DB suffix; generate it
        // up front so TenantResource's form never needs to expose it.
        $data['id'] = (string) Str::uuid();

        return $data;
    }

    /**
     * Tenant::created fires CreateDatabase + MigrateDatabase (queued jobs,
     * see TenancyServiceProvider) — provisioning a new tenant database
     * never blocks this request.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}

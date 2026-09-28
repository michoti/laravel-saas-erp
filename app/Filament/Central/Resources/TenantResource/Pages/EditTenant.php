<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\TenantResource\Pages;

use App\Actions\Tenants\AssignTenantPlan;
use App\Filament\Central\Resources\TenantResource;
use App\Models\Tenant;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalDescription('This permanently deletes the tenant AND its database. This cannot be undone.'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Tenant $tenant */
        $tenant = $this->getRecord();
        $data['plan_id'] = $tenant->subscription?->plan_id;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Tenant $record */
        $planId = Arr::pull($data, 'plan_id');

        DB::connection(config('tenancy.database.central_connection', 'central'))
            ->transaction(function () use ($record, $data, $planId): void {
                $record->update($data);

                if (filled($planId) && (string) $record->subscription?->plan_id !== (string) $planId) {
                    app(AssignTenantPlan::class)->handle($record, $planId);
                }
            });

        return $record;
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\TenantResource\Pages;

use App\Actions\Tenants\AssignTenantPlan;
use App\Models\Tenant;
use App\Filament\Central\Resources\TenantResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

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

    /**
     * The form's Select rule already blocks submitting an inactive plan in
     * the normal UI flow. AssignTenantPlan's own check is defense in depth
     * against a tampered request; if it still trips, surface it as a
     * notification and halt instead of a raw 500.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Tenant $record */
        $planId = Arr::pull($data, 'plan_id');

        try {
            DB::connection(config('tenancy.database.central_connection', 'central'))
                ->transaction(function () use ($record, $data, $planId): void {
                    $record->update($data);

                    if (filled($planId) && (string) $record->subscription?->plan_id !== (string) $planId) {
                        app(AssignTenantPlan::class)->handle($record, $planId);
                    }
                });
        } catch (Throwable $e) {
            Notification::make()
                ->title('Could not update this tenant')
                ->danger()
                ->body($e->getMessage())
                ->persistent()
                ->send();

            throw $e;
        }

        return $record;
    }
}

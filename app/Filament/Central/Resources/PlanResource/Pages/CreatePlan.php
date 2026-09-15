<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\PlanResource\Pages;

use App\Filament\Central\Resources\PlanResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;
}

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Pos\App\Http\Controllers\SyncBatchController;

/*
|--------------------------------------------------------------------------
| Pos Module Routes
|--------------------------------------------------------------------------
| Mounted at /api/pos by PosServiceProvider, behind the `tenant` middleware
| group (InitializeTenancyByDomain, PreventAccessFromCentralDomains,
| EnsureModuleIsEnabled) plus Sanctum auth applied per-route below.
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('sync/batch', [SyncBatchController::class, 'store'])->name('pos.sync.batch.store');
    Route::get('sync/batch/{batch_id}', [SyncBatchController::class, 'show'])->name('pos.sync.batch.show');
});

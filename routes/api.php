<?php

declare(strict_types=1);

use App\Http\Controllers\Billing\SubscriptionMpesaCallbackController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Business-domain routes (POS, Invoicing, CRM, ...) are NOT declared here.
| Each module registers its own routes from its ServiceProvider (see
| Modules/Pos/Providers/PosServiceProvider), keeping every module's
| routes/models/migrations/tests genuinely self-contained per the
| modular-monolith pillar. This file only holds cross-cutting endpoints.
*/

Route::middleware(['auth:sanctum', 'tenant'])->get('/user', fn (Request $request) => $request->user());

/*
|--------------------------------------------------------------------------
| Central Billing Webhook
|--------------------------------------------------------------------------
| Deliberately NOT behind the `tenant` middleware group — subscription
| billing is exclusively central-database data, so this route has no
| tenancy-resolution step at all. See SubscriptionMpesaCallbackController's
| own docblock for why that absence is the point.
*/
Route::post('api/webhooks/mpesa/billing/callback', [SubscriptionMpesaCallbackController::class, 'handle'])
    ->name('billing.mpesa.callback')
    ->middleware(['throttle:120,1', 'mpesa.verify_source']);

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Pos\App\Http\Controllers\MpesaCallbackController;

/*
|--------------------------------------------------------------------------
| Pos Module Webhook Routes
|--------------------------------------------------------------------------
| Public — Safaricom's Daraja API cannot present a Sanctum token or hit a
| tenant subdomain. The tenant is resolved by ID inside the controller
| (see MpesaCallbackController) and tenancy is initialized manually.
| Defense in depth: throttled AND source-IP-verified (see
| App\Http\Middleware\VerifyMpesaWebhookSource) in addition to whatever
| restriction is also applied at the infrastructure layer.
*/

Route::post('api/webhooks/mpesa/{tenant}/callback', [MpesaCallbackController::class, 'handle'])
    ->name('mpesa.callback')
    ->middleware(['throttle:120,1', 'mpesa.verify_source']);

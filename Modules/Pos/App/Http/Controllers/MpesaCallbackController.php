<?php

declare(strict_types=1);

namespace Modules\Pos\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Pos\Services\Mpesa\MpesaPaymentResolver;

final class MpesaCallbackController extends Controller
{
    /**
     * POST /api/webhooks/mpesa/{tenant}/callback  (public, Safaricom-origin only)
     *
     * This route sits outside the `tenant` middleware group (Daraja cannot
     * hit a tenant subdomain), so tenancy is initialized manually from the
     * {tenant} route segment before touching any tenant-scoped model.
     *
     * Safaricom's Daraja API delivers callbacks with at-least-once
     * semantics — duplicates and out-of-order delivery are expected. The
     * actual resolution logic lives in MpesaPaymentResolver, shared with
     * ReconcilePendingMpesaPaymentsCommand, so this handler and the
     * reconciliation sweep can never disagree about what "already
     * processed" means.
     */
    public function handle(Request $request, string $tenant, MpesaPaymentResolver $resolver): JsonResponse
    {
        $tenantModel = Tenant::find($tenant);

        if ($tenantModel === null) {
            Log::warning('mpesa.callback.unknown_tenant', ['tenant' => $tenant]);

            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        tenancy()->initialize($tenantModel);

        $stkCallback = $request->input('Body.stkCallback', []);

        $checkoutRequestId = $stkCallback['CheckoutRequestID'] ?? null;
        $resultCode = $stkCallback['ResultCode'] ?? null;

        if ($checkoutRequestId === null || $resultCode === null) {
            Log::warning('mpesa.callback.malformed', ['payload' => $request->all()]);

            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']); // ack anyway, don't retry us forever
        }

        $mpesaReceipt = collect($stkCallback['CallbackMetadata']['Item'] ?? [])
            ->pluck('Value', 'Name')
            ->get('MpesaReceiptNumber');

        $resolver->resolve(
            checkoutRequestId: $checkoutRequestId,
            resultCode: (int) $resultCode,
            resultDesc: $stkCallback['ResultDesc'] ?? null,
            mpesaReceipt: $mpesaReceipt,
        );

        // Daraja requires this exact envelope shape to stop retrying.
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}

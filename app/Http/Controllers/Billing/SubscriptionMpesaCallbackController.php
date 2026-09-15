<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\SubscriptionPaymentResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * POST /api/webhooks/mpesa/billing/callback — the billing counterpart to
 * Modules\Pos\Http\Controllers\MpesaCallbackController.
 *
 * The single most important property of this controller is what it does
 * NOT do: it never calls tenancy()->initialize() and never touches any
 * tenant database connection. Subscription billing is exclusively
 * central-database data, so this webhook has no code path capable of
 * reaching into a tenant's own database even by accident — unlike the
 * POS webhook, which necessarily must switch tenant context to resolve a
 * sale. That asymmetry IS the privacy boundary described throughout this
 * billing system: central billing data and tenant POS data are not just
 * stored separately, the code that processes each is structurally
 * incapable of reaching the other.
 */
final class SubscriptionMpesaCallbackController extends Controller
{
    public function handle(Request $request, SubscriptionPaymentResolver $resolver): JsonResponse
    {
        $stkCallback = $request->input('Body.stkCallback', []);

        $checkoutRequestId = $stkCallback['CheckoutRequestID'] ?? null;
        $resultCode = $stkCallback['ResultCode'] ?? null;

        if ($checkoutRequestId === null || $resultCode === null) {
            Log::channel('mpesa_critical')->warning('billing.callback.malformed', ['payload' => $request->all()]);

            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
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

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}

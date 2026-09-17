<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defense in depth for both M-Pesa webhook endpoints (POS sales and
 * subscription billing): Safaricom publishes a fixed set of Daraja
 * callback source IP ranges. Requests from outside that allowlist are
 * rejected before either webhook controller runs any business logic,
 * so a discovered webhook URL alone is not enough to inject a fake
 * "payment succeeded" callback — an attacker would also need to spoof
 * a Safaricom-owned source address, which is blocked well upstream of
 * this middleware at the network layer in a real deployment, and
 * checked again here as a second, independent layer.
 *
 * Configured via MPESA_ALLOWED_CALLBACK_IPS (comma-separated CIDR
 * ranges); left empty in local/sandbox environments so development
 * against the Daraja sandbox isn't blocked by an incomplete allowlist.
 */
final class VerifyMpesaWebhookSource
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedRanges = array_filter(explode(',', (string) config('mpesa.allowed_callback_ips', '')));

        if (empty($allowedRanges)) {
            return $next($request);
        }

        $ip = $request->ip();

        foreach ($allowedRanges as $range) {
            if ($this->ipMatchesCidr($ip, trim($range))) {
                return $next($request);
            }
        }

        Log::channel('mpesa_critical')->warning('mpesa.callback.rejected_source_ip', ['ip' => $ip, 'path' => $request->path()]);

        abort(403, 'This source is not permitted to call this endpoint.');
    }

    private function ipMatchesCidr(string $ip, string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $bits] = explode('/', $cidr);
        $bits = (int) $bits;

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = -1 << (32 - $bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}

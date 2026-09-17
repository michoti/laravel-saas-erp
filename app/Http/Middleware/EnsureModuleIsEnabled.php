<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureModuleIsEnabled
{
    /**
     * Usage: ->middleware('module:pos') on a route/group, or applied
     * globally by a module's ServiceProvider with a fixed key.
     */
    public function handle(Request $request, Closure $next, ?string $moduleKey = null): Response
    {
        $tenant = tenant();

        if ($tenant === null) {
            abort(403, 'No active tenant context.');
        }

        $key = $moduleKey ?? $this->guessModuleKeyFromPath($request);

        if ($key !== null && ! $tenant->hasModuleEnabled($key)) {
            abort(403, "The \"{$key}\" module is not enabled for this account.");
        }

        return $next($request);
    }

    private function guessModuleKeyFromPath(Request $request): ?string
    {
        // /api/pos/... -> 'pos'
        $segments = $request->segments();

        return $segments[1] ?? null;
    }
}

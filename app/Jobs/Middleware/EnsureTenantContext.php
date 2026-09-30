<?php

declare(strict_types=1);

namespace App\Jobs\Middleware;

use App\Models\Tenant;
use RuntimeException;
use Stancl\Tenancy\Facades\Tenancy;

/**
 * Defense-in-depth on top of QueueTenancyBootstrapper (already enabled in
 * config/tenancy.php). That bootstrapper is what actually does the real
 * work: it tags a job's queue payload with the current tenant at dispatch
 * time, and re-initializes that same tenant automatically on the worker
 * before the job runs — this is why a job dispatched from inside a tenant
 * request already runs against the right tenant database with no extra
 * code, and why Tenant A's job can never silently execute under Tenant B's
 * connection.
 *
 * This middleware adds one thing on top: a loud, explicit failure if a
 * tenant-scoped job somehow reaches its handle() with NO tenant initialized
 * at all (e.g. it was dispatched from a central/console context that forgot
 * to wrap the dispatch in `tenancy()->run($tenant, fn () => ...)`), instead
 * of quietly running against the central connection and writing a
 * notification, cache key, or DB row to the wrong place.
 *
 * Usage, on any tenant-scoped job/notification's `middleware()` method:
 *   public function middleware(): array
 *   {
 *       return [new EnsureTenantContext($this->tenantId)];
 *   }
 *
 * `$expectedTenantId` should be captured in the job's constructor (a plain
 * string property, NOT the Tenant model itself — never serialize a full
 * Eloquent model tied to a specific connection into a queued job payload).
 */
final class EnsureTenantContext
{
    public function __construct(
        private readonly string $expectedTenantId,
    ) {}

    public function handle(mixed $job, callable $next): mixed
    {
        $current = Tenancy::tenant();

        if (! $current instanceof Tenant) {
            throw new RuntimeException(
                "Tenant-scoped job expected tenant [{$this->expectedTenantId}] to already be initialized " .
                'by QueueTenancyBootstrapper, but no tenant is active. Refusing to run against the central ' .
                'connection. If this job is dispatched from a central/console context, wrap the dispatch in ' .
                "tenancy()->run(\$tenant, fn () => ...) so the correct tenant is tagged onto the job payload.",
            );
        }

        if ($current->getKey() !== $this->expectedTenantId) {
            throw new RuntimeException(
                "Tenant-scoped job expected tenant [{$this->expectedTenantId}] but tenant " .
                "[{$current->getKey()}] is active. Refusing to run to avoid cross-tenant data exposure.",
            );
        }

        return $next($job);
    }
}

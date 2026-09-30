<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;
use Stancl\Tenancy\Facades\Tenancy;

// Reverb, running under RedisTenancyBootstrapper's prefixed connection, so
// this channel name is naturally isolated per tenant — no tenant_id needed
// in the channel name itself.
Broadcast::channel('pos.sync', fn ($user) => $user !== null);

/**
 * Per-user notification channel (see App\Notifications\TenantAwareNotification
 * ::broadcastChannelFor()). Reverb itself has NO concept of tenancy — a
 * single Reverb server/app key is shared across every tenant — so the
 * tenant id is embedded directly in the channel name, and BOTH the tenant
 * id and the user id are checked here before authorizing the socket. A
 * user from tenant A can never subscribe to tenant B's channel even if
 * they somehow learned tenant B's channel name, and a user can never
 * subscribe to another user's channel within their own tenant either.
 */
Broadcast::channel('tenant.{tenantId}.user.{userId}', function ($user, string $tenantId, string $userId): bool {
    $currentTenantId = Tenancy::tenant()?->getKey();

    return $currentTenantId !== null
        && $currentTenantId === $tenantId
        && (string) $user->getKey() === $userId;
});

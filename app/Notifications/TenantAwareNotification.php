<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Stancl\Tenancy\Facades\Tenancy;

/**
 * Base class for every tenant-scoped notification. Handles the two cross-
 * cutting concerns every such notification needs, so individual
 * notifications (see LowStockAlert for an example) only implement their
 * own content:
 *
 * 1. DYNAMIC CHANNEL ROUTING: `via()` reads the notifiable user's own
 *    stored preference (App\Models\User::preferredNotificationChannels())
 *    rather than hardcoding channels per notification class, intersected
 *    against whatever channels THIS notification actually knows how to
 *    render (a notification with no toMail()/toBroadcast() shouldn't be
 *    silently "sent" through a channel it can't format for).
 * 2. TENANT-SCOPED BROADCAST CHANNELS: `broadcastChannelFor()` builds the
 *    tenant-prefixed private channel name, matching the authorization rule
 *    registered in routes/channels.php. Reverb itself has no concept of
 *    tenancy — this prefix is the ONLY thing preventing one tenant's
 *    broadcast from being addressable by another tenant's socket.
 *
 * Queued by default (`ShouldQueue`): every concrete notification runs
 * through Laravel's SendQueuedNotifications job, which QueueTenancyBootstrapper
 * tags with the current tenant at dispatch time — see EnsureTenantContext's
 * docblock for the full chain.
 */
abstract class TenantAwareNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Channels this notification class can actually render. Concrete
     * notifications declare this instead of overriding via() directly, so
     * the preference-intersection logic below stays in one place.
     *
     * @return list<string>
     */
    abstract protected function supportedChannels(): array;

    /**
     * @return list<string>
     */
    public function via(mixed $notifiable): array
    {
        $preferred = $notifiable instanceof User
            ? $notifiable->preferredNotificationChannels()
            : ['database'];

        $channels = array_values(array_intersect($preferred, $this->supportedChannels()));

        // Never silently deliver nothing: if the user's preferences don't
        // overlap with what this notification can render at all, fall back
        // to 'database' so it's at least visible in the panel's bell icon.
        return $channels !== [] ? $channels : ['database'];
    }

    /**
     * The tenant-prefixed private channel a `broadcast` delivery for
     * `$notifiable` should target. Matches the authorization callback
     * registered in routes/channels.php — keep both in sync.
     */
    protected function broadcastChannelFor(mixed $notifiable): string
    {
        $tenantId = Tenancy::tenant()?->getKey();
        $userId = $notifiable instanceof User ? $notifiable->getKey() : $notifiable;

        return "private-tenant.{$tenantId}.user.{$userId}";
    }
}

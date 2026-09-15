<?php

declare(strict_types=1);

namespace Modules\Pos\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TenantDataSynced implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $batchId,
        public readonly array $summary,
    ) {}

    public function broadcastOn(): array
    {
        // The tenant's Redis connection is already prefixed by
        // RedisTenancyBootstrapper, and Reverb picks up the tenant-scoped
        // broadcast connection, so a private channel name alone is enough
        // to keep this isolated per tenant.
        return [new Channel('pos.sync')];
    }

    public function broadcastAs(): string
    {
        return 'batch.synced';
    }
}

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

// Reverb, running under RedisTenancyBootstrapper's prefixed connection, so
// this channel name is naturally isolated per tenant — no tenant_id needed
// in the channel name itself.
Broadcast::channel('pos.sync', fn ($user) => $user !== null);

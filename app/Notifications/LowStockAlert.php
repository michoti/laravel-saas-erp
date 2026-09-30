<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Jobs\Middleware\EnsureTenantContext;
use App\Models\User;
use App\Support\Notifications\NotifiableRecipients;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Pos\App\Models\Product;
use Stancl\Tenancy\Facades\Tenancy;

/**
 * Worked example of the full pattern: dispatch via `send()` below (not
 * `new self(...)` scattered around the codebase), which resolves recipients
 * by SPATIE PERMISSION rather than a hardcoded user list, tags the job with
 * the current tenant for EnsureTenantContext, and lets each recipient's own
 * channel preference decide mail vs database vs broadcast (or several).
 *
 * Only scalar data is stored on the notification (never the Product model
 * itself, and never a Tenant model) — a queued job payload should hold
 * plain values, not models bound to a specific tenant's database connection.
 */
final class LowStockAlert extends TenantAwareNotification
{
    private readonly string $tenantId;

    public function __construct(
        private readonly string $productId,
        private readonly string $productName,
        private readonly string $sku,
        private readonly float $quantityRemaining,
    ) {
        $tenant = Tenancy::tenant();

        // Constructing this outside a tenant context is a programming
        // error, not a runtime condition to degrade gracefully from — see
        // EnsureTenantContext for the matching worker-side guard.
        $this->tenantId = $tenant?->getKey() ?? throw new \RuntimeException(
            'LowStockAlert must be constructed while tenancy is initialized.',
        );
    }

    /**
     * The one place this notification is dispatched from. Call this
     * instead of `Notification::send()` directly, so the recipient query
     * and the notification's own construction can never drift apart.
     */
    public static function sendFor(Product $product): void
    {
        $recipients = NotifiableRecipients::withAnyPermission(['manage_inventory', 'view_inventory']);

        if ($recipients->isEmpty()) {
            return;
        }

        \Illuminate\Support\Facades\Notification::send($recipients, new self(
            productId: (string) $product->getKey(),
            productName: $product->name,
            sku: $product->sku,
            quantityRemaining: (float) ($product->quantity_on_hand ?? 0),
        ));
    }

    public function middleware(): array
    {
        return [new EnsureTenantContext($this->tenantId)];
    }

    protected function supportedChannels(): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Low stock: {$this->productName}")
            ->line("{$this->productName} (SKU {$this->sku}) is running low.")
            ->line("Quantity remaining: {$this->quantityRemaining}")
            ->action('View product', url("/app/pos/products/{$this->productId}"));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'sku' => $this->sku,
            'quantity_remaining' => $this->quantityRemaining,
            'severity' => $this->quantityRemaining <= 0 ? 'critical' : 'warning',
        ];
    }

    public function toBroadcast(mixed $notifiable): BroadcastMessage
    {
        return (new BroadcastMessage([
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'sku' => $this->sku,
            'quantity_remaining' => $this->quantityRemaining,
        ]))->onQueue('notifications-high');
    }

    /**
     * Channel routing lives here (the notification's own method), not on
     * BroadcastMessage — Laravel calls this separately when delivering a
     * `broadcast` channel notification.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(mixed $notifiable): array
    {
        return [new \Illuminate\Broadcasting\PrivateChannel($this->broadcastChannelFor($notifiable))];
    }
}

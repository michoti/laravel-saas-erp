<?php

declare(strict_types=1);

namespace Modules\Pos\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Pos\App\Models\Payment;

final class PaymentConfirmed implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly Payment $payment) {}

    public function broadcastOn(): array
    {
        return [new Channel('pos.sync')];
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->payment->order_id,
            'payment_id' => $this->payment->id,
            'status' => $this->payment->status,
            'mpesa_receipt_number' => $this->payment->mpesa_receipt_number,
        ];
    }

    public function broadcastAs(): string
    {
        return 'payment.confirmed';
    }
}

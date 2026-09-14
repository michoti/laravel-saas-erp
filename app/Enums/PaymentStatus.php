<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case AwaitingConfirmation = 'awaiting_confirmation';
    case Completed = 'completed';
    case Failed = 'failed';
    case Reversed = 'reversed';

    public function isResolved(): bool
    {
        return $this === self::Completed || $this === self::Failed;
    }

    public function color(): string
    {
        return match ($this) {
            self::Completed => 'success',
            self::Pending, self::AwaitingConfirmation => 'warning',
            self::Failed, self::Reversed => 'danger',
        };
    }
}

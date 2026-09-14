<?php

declare(strict_types=1);

namespace App\Enums;

enum SubscriptionInvoiceStatus: string
{
    case Pending = 'pending';
    case AwaitingConfirmation = 'awaiting_confirmation';
    case Paid = 'paid';
    case Failed = 'failed';
    case Void = 'void';

    public function isResolved(): bool
    {
        return $this === self::Paid || $this === self::Failed;
    }

    public function color(): string
    {
        return match ($this) {
            self::Paid => 'success',
            self::Pending, self::AwaitingConfirmation => 'warning',
            self::Failed, self::Void => 'danger',
        };
    }
}

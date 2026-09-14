<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case Draft = 'draft';
    case AwaitingPayment = 'awaiting_payment';
    case Paid = 'paid';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';
    case Voided = 'voided';

    public function color(): string
    {
        return match ($this) {
            self::Paid => 'success',
            self::AwaitingPayment => 'warning',
            self::Voided, self::Refunded => 'danger',
            self::PartiallyRefunded => 'warning',
            self::Draft => 'gray',
        };
    }
}

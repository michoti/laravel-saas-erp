<?php

declare(strict_types=1);

namespace App\Enums;

enum StockMovementType: string
{
    case Sale = 'sale';
    case Return = 'return';
    case Purchase = 'purchase';
    case Adjustment = 'adjustment';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';

    /** Whether this movement type normally carries a positive (inbound) delta. */
    public function isInbound(): bool
    {
        return match ($this) {
            self::Purchase, self::Return, self::TransferIn => true,
            self::Sale, self::TransferOut, self::Adjustment => false,
        };
    }
}

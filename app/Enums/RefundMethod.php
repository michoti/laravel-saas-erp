<?php

declare(strict_types=1);

namespace App\Enums;

enum RefundMethod: string
{
    case Cash = 'cash';
    case MpesaManual = 'mpesa_manual';
    case StoreCredit = 'store_credit';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::MpesaManual => 'M-Pesa (manual reversal)',
            self::StoreCredit => 'Store credit',
        };
    }
}

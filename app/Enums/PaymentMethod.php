<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Mpesa = 'mpesa';
    case Card = 'card';
    case StoreCredit = 'store_credit';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Mpesa => 'M-Pesa',
            default => ucfirst(str_replace('_', ' ', $this->value)),
        };
    }
}

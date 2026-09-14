<?php

declare(strict_types=1);

namespace App\Enums;

enum PromotionDiscountType: string
{
    case Percentage = 'percentage';
    case FixedAmount = 'fixed_amount';

    public function apply(float $unitPrice, float $discountValue): float
    {
        return match ($this) {
            self::Percentage => round($unitPrice * (1 - ($discountValue / 100)), 2),
            self::FixedAmount => max(0.0, round($unitPrice - $discountValue, 2)),
        };
    }
}

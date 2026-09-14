<?php

declare(strict_types=1);

namespace Modules\Pos\DataTransferObjects;

use App\Enums\PaymentMethod;

/** One tender line in a split payment — see CartLineData's docblock for why this is a DTO rather than an array. */
final readonly class PaymentLineData
{
    public function __construct(
        public PaymentMethod $method,
        public float $amount,
        public ?string $payerPhone = null,
    ) {}

    /** @param array{method: string, amount: float|int, payer_phone?: ?string} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            method: PaymentMethod::from($data['method']),
            amount: (float) $data['amount'],
            payerPhone: $data['payer_phone'] ?? null,
        );
    }
}

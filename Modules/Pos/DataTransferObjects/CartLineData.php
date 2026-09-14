<?php

declare(strict_types=1);

namespace Modules\Pos\DataTransferObjects;

/**
 * One line of a POS Terminal cart, or one item within an offline sync
 * batch payload once validated — replaces the loosely-typed associative
 * arrays PosCheckoutService previously passed around internally.
 * Immutable by construction (PHP 8.4 readonly).
 *
 * Deliberately carries `unitPrice` (the negotiated/promotional price
 * shown to the cashier) but NOT a tax rate: tax is always re-derived
 * authoritatively from the Product record inside PosCheckoutService's
 * transaction, never trusted from the caller — even a server-side
 * Livewire component's public properties are technically tamperable via
 * a crafted wire:model payload, so pricing-critical derived values like
 * tax are re-verified server-side regardless of what this DTO carries.
 */
final readonly class CartLineData
{
    public function __construct(
        public string $productId,
        public float $quantity,
        public float $unitPrice,
    ) {}

    /** @param array{product_id: string, quantity: float|int, unit_price: float} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: $data['product_id'],
            quantity: (float) $data['quantity'],
            unitPrice: (float) $data['unit_price'],
        );
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command;

/**
 * Command data class for OrderItem.
 *
 * Readonly DTO for command operations.
 */
readonly class OrderItem
{
    public function __construct(
        public string $productIdentifier,
        public int $quantity,
        public float $unitPrice,
        public float $subtotal,
        /** @var array<ItemDiscount> */
        public array $itemDiscount = []
    ) {
    }
}

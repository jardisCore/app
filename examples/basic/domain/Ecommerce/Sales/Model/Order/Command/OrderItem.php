<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command;

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
        public string $unitPrice,
        public string $subtotal,
        /** @var array<ItemDiscount> */
        public array $itemDiscount = []
    ) {
    }
}

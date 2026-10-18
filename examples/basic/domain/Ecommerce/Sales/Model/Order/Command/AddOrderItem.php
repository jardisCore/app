<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command;

/**
 * Command data class for AddOrderItem.
 *
 * Readonly DTO for command operations.
 */
readonly class AddOrderItem
{
    public function __construct(
        public string $orderNumber,
        public string $productIdentifier,
        public int $quantity,
        public string $unitPrice,
        public string $subtotal
    ) {
    }
}

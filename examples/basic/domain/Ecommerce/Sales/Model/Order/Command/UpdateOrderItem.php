<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command;

/**
 * Command data class for UpdateOrderItem.
 *
 * Readonly DTO for command operations.
 */
readonly class UpdateOrderItem
{
    public function __construct(
        public string $orderNumber,
        public string $orderItemIdentifier,
        public string $productIdentifier,
        public int $quantity,
        public string $unitPrice,
        public string $subtotal
    ) {
    }
}

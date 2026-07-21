<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command;

/**
 * Command data class for RemoveItemDiscount.
 *
 * Readonly DTO for command operations.
 */
readonly class RemoveItemDiscount
{
    public function __construct(
        public string $orderNumber,
        public string $orderItemIdentifier,
        public int $itemDiscountId
    ) {
    }
}

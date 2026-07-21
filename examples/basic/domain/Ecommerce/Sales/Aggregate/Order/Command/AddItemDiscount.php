<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command;

/**
 * Command data class for AddItemDiscount.
 *
 * Readonly DTO for command operations.
 */
readonly class AddItemDiscount
{
    public function __construct(
        public string $orderNumber,
        public string $orderItemIdentifier,
        public string $discountCode,
        public float $amount
    ) {
    }
}

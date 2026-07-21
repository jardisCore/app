<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command;

/**
 * Command data class for ItemDiscount.
 *
 * Readonly DTO for command operations.
 */
readonly class ItemDiscount
{
    public function __construct(
        public string $discountCode,
        public float $amount
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Query;

/**
 * Query data class for OrderByOrderNumber.
 *
 * Readonly DTO for query operations.
 */
readonly class OrderByOrderNumber
{
    public function __construct(
        public string $orderNumber
    ) {
    }
}

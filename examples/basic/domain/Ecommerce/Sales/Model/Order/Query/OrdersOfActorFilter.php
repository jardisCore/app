<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Query;

/**
 * Query data class for OrdersOfActorFilter.
 *
 * Readonly DTO for query operations.
 */
readonly class OrdersOfActorFilter
{
    public function __construct(
        public int $limit = 20,
        public int $offset = 0
    ) {
    }
}

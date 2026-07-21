<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Query;

/**
 * Query data class for OrderById.
 *
 * Readonly DTO for query operations.
 */
readonly class OrderById
{
    public function __construct(
        public int $id
    ) {
    }
}

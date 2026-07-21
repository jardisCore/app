<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Query;

/**
 * Query data class for OrderByIds.
 *
 * Readonly DTO for query operations.
 */
readonly class OrderByIds
{
    public function __construct(
        public array $ids
    ) {
    }
}

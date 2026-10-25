<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Query;

/**
 * Query data class for OrderByOrderNumbers.
 *
 * Readonly DTO for query operations.
 */
readonly class OrderByOrderNumbers
{
    public function __construct(
        public string $tenant,
        public array $orderNumbers
    ) {
    }
}

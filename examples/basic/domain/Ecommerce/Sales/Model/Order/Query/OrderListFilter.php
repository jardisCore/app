<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Query;

/**
 * Query data class for OrderListFilter.
 *
 * Readonly DTO for query operations.
 */
readonly class OrderListFilter
{
    public function __construct(
        public ?int $id = null,
        public ?string $orderNumber = null,
        public ?int $customerId = null,
        /** @var ?numeric-string */
        public ?string $totalAmount = null,
        public ?string $status = null,
        public ?string $createdAtFrom = null,
        public ?string $createdAtUntil = null,
        public int $limit = 20,
        public int $offset = 0
    ) {
    }
}

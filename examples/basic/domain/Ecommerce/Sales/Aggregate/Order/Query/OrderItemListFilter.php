<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Query;

/**
 * Filter DTO for OrderItemList list query.
 *
 * Generated code - do not modify directly.
 */
readonly class OrderItemListFilter
{
    public function __construct(
        public string $orderNumber,
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Query;

/**
 * Filter DTO for OrderList list query.
 *
 * Generated code - do not modify directly.
 */
readonly class OrderListFilter
{
    public function __construct(
        public ?string $status = null,
        public ?string $search = null,
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}

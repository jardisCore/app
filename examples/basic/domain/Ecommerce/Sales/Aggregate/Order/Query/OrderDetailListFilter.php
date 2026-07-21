<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Query;

/**
 * Filter DTO for OrderDetailList list query.
 *
 * Generated code - do not modify directly.
 */
readonly class OrderDetailListFilter
{
    public function __construct(
        public ?string $status = null,
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}

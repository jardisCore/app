<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Event;

use DateTimeImmutable;

/**
 * Event: ProductImage was removed.
 *
 * Dispatched after a ProductImage is removed from the aggregate.
 */
readonly class ProductProductImageRemoved
{
    /**
     * @param string $productIdentifier
     * @param int $productImageId
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $productIdentifier,
        public int $productImageId,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

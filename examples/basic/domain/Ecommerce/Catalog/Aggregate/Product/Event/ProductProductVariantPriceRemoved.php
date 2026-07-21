<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Event;

use DateTimeImmutable;

/**
 * Event: ProductVariantPrice was removed.
 *
 * Dispatched after a ProductVariantPrice is removed from the aggregate.
 */
readonly class ProductProductVariantPriceRemoved
{
    /**
     * @param string $productIdentifier
     * @param int $productVariantPriceId
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $productIdentifier,
        public int $productVariantPriceId,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

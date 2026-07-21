<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Event;

use DateTimeImmutable;

/**
 * Event: ProductVariant was removed.
 *
 * Dispatched after a ProductVariant is removed from the aggregate.
 */
readonly class ProductProductVariantRemoved
{
    /**
     * @param string $productIdentifier
     * @param string $productVariantIdentifier
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $productIdentifier,
        public string $productVariantIdentifier,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

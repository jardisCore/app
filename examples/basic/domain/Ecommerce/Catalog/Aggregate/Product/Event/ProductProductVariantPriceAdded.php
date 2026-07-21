<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Event;

use DateTimeImmutable;

/**
 * Event: ProductVariantPrice was added.
 *
 * Dispatched after a new ProductVariantPrice is added to the aggregate.
 */
readonly class ProductProductVariantPriceAdded
{
    /**
     * @param string $productIdentifier
     * @param int $productVariantPriceId
     * @param string $region
     * @param float $price
     * @param string $currency
     * @param ?DateTimeImmutable $validFrom
     * @param ?DateTimeImmutable $validUntil
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $productIdentifier,
        public int $productVariantPriceId,
        public string $region,
        public float $price,
        public string $currency,
        public ?DateTimeImmutable $validFrom,
        public ?DateTimeImmutable $validUntil,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

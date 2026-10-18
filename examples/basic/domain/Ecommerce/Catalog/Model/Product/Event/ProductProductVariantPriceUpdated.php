<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Event;

use DateTimeImmutable;

/**
 * Event: ProductVariantPrice was updated.
 *
 * Dispatched after ProductVariantPrice fields are successfully persisted.
 */
readonly class ProductProductVariantPriceUpdated
{
    /**
     * @param string $productIdentifier
     * @param int $productVariantPriceId
     * @param string $region
     * @param numeric-string $price
     * @param string $currency
     * @param ?DateTimeImmutable $validFrom
     * @param ?DateTimeImmutable $validUntil
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $productIdentifier,
        public int $productVariantPriceId,
        public string $region,
        public string $price,
        public string $currency,
        public ?DateTimeImmutable $validFrom,
        public ?DateTimeImmutable $validUntil,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

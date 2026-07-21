<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Event;

use DateTimeImmutable;

/**
 * Event: Product was updated.
 *
 * Dispatched after Product fields are successfully persisted.
 */
readonly class ProductUpdated
{
    /**
     * @param string $productIdentifier
     * @param string $sku
     * @param string $productName
     * @param string $slug
     * @param ?string $description
     * @param ?string $shortDescription
     * @param float $price
     * @param ?float $compareAtPrice
     * @param ?float $costPrice
     * @param string $currency
     * @param ?int $weightGrams
     * @param int $isActive
     * @param int $isFeatured
     * @param string $taxClass
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $productIdentifier,
        public string $sku,
        public string $productName,
        public string $slug,
        public ?string $description,
        public ?string $shortDescription,
        public float $price,
        public ?float $compareAtPrice,
        public ?float $costPrice,
        public string $currency,
        public ?int $weightGrams,
        public int $isActive,
        public int $isFeatured,
        public string $taxClass,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

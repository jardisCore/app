<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Event;

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
     * @param string $tenantKey
     * @param string $sku
     * @param string $productName
     * @param string $slug
     * @param ?string $description
     * @param ?string $shortDescription
     * @param string $price
     * @param ?string $compareAtPrice
     * @param ?string $costPrice
     * @param string $currency
     * @param ?int $weightGrams
     * @param int $isActive
     * @param int $isFeatured
     * @param string $taxClass
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $productIdentifier,
        public string $tenantKey,
        public string $sku,
        public string $productName,
        public string $slug,
        public ?string $description,
        public ?string $shortDescription,
        public string $price,
        public ?string $compareAtPrice,
        public ?string $costPrice,
        public string $currency,
        public ?int $weightGrams,
        public int $isActive,
        public int $isFeatured,
        public string $taxClass,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

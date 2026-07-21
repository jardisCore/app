<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Event;

use DateTimeImmutable;

/**
 * Event: ProductVariant was added.
 *
 * Dispatched after a new ProductVariant is added to the aggregate.
 */
readonly class ProductProductVariantAdded
{
    /**
     * @param string $productIdentifier
     * @param string $productVariantIdentifier
     * @param string $sku
     * @param string $variantName
     * @param string $optionName
     * @param string $optionValue
     * @param float $priceModifier
     * @param int $stockQuantity
     * @param ?int $lowStockThreshold
     * @param ?int $weightGrams
     * @param int $isAvailable
     * @param int $sortOrder
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $productIdentifier,
        public string $productVariantIdentifier,
        public string $sku,
        public string $variantName,
        public string $optionName,
        public string $optionValue,
        public float $priceModifier,
        public int $stockQuantity,
        public ?int $lowStockThreshold,
        public ?int $weightGrams,
        public int $isAvailable,
        public int $sortOrder,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

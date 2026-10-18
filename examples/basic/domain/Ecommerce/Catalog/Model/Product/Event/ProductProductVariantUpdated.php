<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Event;

use DateTimeImmutable;

/**
 * Event: ProductVariant was updated.
 *
 * Dispatched after ProductVariant fields are successfully persisted.
 */
readonly class ProductProductVariantUpdated
{
    /**
     * @param string $productIdentifier
     * @param string $productVariantIdentifier
     * @param string $sku
     * @param string $variantName
     * @param string $optionName
     * @param string $optionValue
     * @param numeric-string $priceModifier
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
        public string $priceModifier,
        public int $stockQuantity,
        public ?int $lowStockThreshold,
        public ?int $weightGrams,
        public int $isAvailable,
        public int $sortOrder,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Command;

/**
 * Command data class for ProductVariant.
 *
 * Readonly DTO for command operations.
 */
readonly class ProductVariant
{
    public function __construct(
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
        /** @var array<ProductVariantPrice> */
        public array $productVariantPrice
    ) {
    }
}

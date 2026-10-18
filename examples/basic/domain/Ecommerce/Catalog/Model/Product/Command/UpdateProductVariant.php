<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command;

/**
 * Command data class for UpdateProductVariant.
 *
 * Readonly DTO for command operations.
 */
readonly class UpdateProductVariant
{
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
        public int $sortOrder
    ) {
    }
}

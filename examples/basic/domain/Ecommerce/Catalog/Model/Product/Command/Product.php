<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command;

/**
 * Command data class for Product.
 *
 * Readonly DTO for command operations.
 */
readonly class Product
{
    public function __construct(
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
        /** @var array<ProductVariant> */
        public array $productVariant,
        /** @var array<ProductImage> */
        public array $productImage = []
    ) {
    }
}

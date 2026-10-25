<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Query;

/**
 * Query data class for ProductListFilter.
 *
 * Readonly DTO for query operations.
 */
readonly class ProductListFilter
{
    public function __construct(
        public string $tenant,
        public ?int $id = null,
        public ?string $identifier = null,
        public ?string $sku = null,
        public ?string $productName = null,
        public ?string $slug = null,
        public ?string $description = null,
        public ?string $shortDescription = null,
        public ?float $price = null,
        public ?float $compareAtPrice = null,
        public ?float $costPrice = null,
        public ?string $currency = null,
        public ?int $weightGrams = null,
        public ?int $isActive = null,
        public ?int $isFeatured = null,
        public ?string $taxClass = null,
        public ?string $createdAtFrom = null,
        public ?string $createdAtUntil = null,
        public ?string $updatedAtFrom = null,
        public ?string $updatedAtUntil = null,
        public int $limit = 20,
        public int $offset = 0
    ) {
    }
}

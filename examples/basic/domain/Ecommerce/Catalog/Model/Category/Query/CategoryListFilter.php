<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Query;

/**
 * Query data class for CategoryListFilter.
 *
 * Readonly DTO for query operations.
 */
readonly class CategoryListFilter
{
    public function __construct(
        public string $tenant,
        public ?int $id = null,
        public ?string $identifier = null,
        public ?string $slug = null,
        public ?string $parentIdentifier = null,
        public ?string $categoryName = null,
        public ?string $description = null,
        public ?string $metaTitle = null,
        public ?string $metaDescription = null,
        public ?int $isActive = null,
        public ?int $isVisible = null,
        public ?int $sortOrder = null,
        public ?int $productCount = null,
        public ?string $createdAtFrom = null,
        public ?string $createdAtUntil = null,
        public ?string $updatedAtFrom = null,
        public ?string $updatedAtUntil = null,
        public int $limit = 20,
        public int $offset = 0
    ) {
    }
}

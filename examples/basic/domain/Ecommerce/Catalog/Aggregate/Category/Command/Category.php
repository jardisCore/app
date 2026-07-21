<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Command;

/**
 * Command data class for Category.
 *
 * Readonly DTO for command operations.
 */
readonly class Category
{
    public function __construct(
        public string $slug,
        public ?string $parentIdentifier,
        public string $categoryName,
        public ?string $description,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public int $isActive,
        public int $isVisible,
        public int $sortOrder,
        public int $productCount,
        /** @var array<CategoryTranslation> */
        public array $categoryTranslation = []
    ) {
    }
}

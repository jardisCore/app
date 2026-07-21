<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Command;

/**
 * Command data class for UpdateCategory.
 *
 * Readonly DTO for command operations.
 */
readonly class UpdateCategory
{
    public function __construct(
        public string $categoryIdentifier,
        public string $slug,
        public ?string $parentIdentifier,
        public string $categoryName,
        public ?string $description,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public int $isActive,
        public int $isVisible,
        public int $sortOrder,
        public int $productCount
    ) {
    }
}

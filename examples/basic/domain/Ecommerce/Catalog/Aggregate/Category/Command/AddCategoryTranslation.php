<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Command;

/**
 * Command data class for AddCategoryTranslation.
 *
 * Readonly DTO for command operations.
 */
readonly class AddCategoryTranslation
{
    public function __construct(
        public string $categoryIdentifier,
        public string $locale,
        public string $title,
        public ?string $description,
        public ?string $metaTitle,
        public ?string $metaDescription
    ) {
    }
}

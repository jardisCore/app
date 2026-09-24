<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Command;

/**
 * Command data class for CategoryTranslation.
 *
 * Readonly DTO for command operations.
 */
readonly class CategoryTranslation
{
    public function __construct(
        public string $locale,
        public string $title,
        public ?string $description,
        public ?string $metaTitle,
        public ?string $metaDescription
    ) {
    }
}

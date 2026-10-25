<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Command;

/**
 * Command data class for UpdateCategoryTranslation.
 *
 * Readonly DTO for command operations.
 */
readonly class UpdateCategoryTranslation
{
    public function __construct(
        public string $tenant,
        public string $categoryIdentifier,
        public int $categoryTranslationId,
        public string $locale,
        public string $title,
        public ?string $description,
        public ?string $metaTitle,
        public ?string $metaDescription
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Command;

/**
 * Command data class for RemoveCategoryTranslation.
 *
 * Readonly DTO for command operations.
 */
readonly class RemoveCategoryTranslation
{
    public function __construct(
        public string $categoryIdentifier,
        public int $categoryTranslationId
    ) {
    }
}

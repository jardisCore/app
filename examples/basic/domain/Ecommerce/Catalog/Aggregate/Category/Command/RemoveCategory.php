<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Command;

/**
 * Command data class for RemoveCategory.
 *
 * Readonly DTO for command operations.
 */
readonly class RemoveCategory
{
    public function __construct(
        public string $categoryIdentifier
    ) {
    }
}

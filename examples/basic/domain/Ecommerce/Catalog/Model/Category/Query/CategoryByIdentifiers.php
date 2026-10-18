<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Query;

/**
 * Query data class for CategoryByIdentifiers.
 *
 * Readonly DTO for query operations.
 */
readonly class CategoryByIdentifiers
{
    public function __construct(
        public array $identifiers
    ) {
    }
}

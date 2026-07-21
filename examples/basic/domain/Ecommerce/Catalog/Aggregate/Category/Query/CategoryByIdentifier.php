<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Query;

/**
 * Query data class for CategoryByIdentifier.
 *
 * Readonly DTO for query operations.
 */
readonly class CategoryByIdentifier
{
    public function __construct(
        public string $identifier
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Query;

/**
 * Query data class for CategoryByIds.
 *
 * Readonly DTO for query operations.
 */
readonly class CategoryByIds
{
    public function __construct(
        public array $ids
    ) {
    }
}

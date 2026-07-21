<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Query;

/**
 * Query data class for CategoryById.
 *
 * Readonly DTO for query operations.
 */
readonly class CategoryById
{
    public function __construct(
        public int $id
    ) {
    }
}

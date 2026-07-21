<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Query;

/**
 * Query data class for ProductByIds.
 *
 * Readonly DTO for query operations.
 */
readonly class ProductByIds
{
    public function __construct(
        public array $ids
    ) {
    }
}

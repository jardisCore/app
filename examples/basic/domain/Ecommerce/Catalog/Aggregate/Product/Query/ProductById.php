<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Query;

/**
 * Query data class for ProductById.
 *
 * Readonly DTO for query operations.
 */
readonly class ProductById
{
    public function __construct(
        public int $id
    ) {
    }
}

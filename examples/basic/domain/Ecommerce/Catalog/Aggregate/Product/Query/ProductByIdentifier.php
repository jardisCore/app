<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Query;

/**
 * Query data class for ProductByIdentifier.
 *
 * Readonly DTO for query operations.
 */
readonly class ProductByIdentifier
{
    public function __construct(
        public string $identifier
    ) {
    }
}

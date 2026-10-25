<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Query;

/**
 * Query data class for ProductByIdentifiers.
 *
 * Readonly DTO for query operations.
 */
readonly class ProductByIdentifiers
{
    public function __construct(
        public string $tenant,
        public array $identifiers
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Query;

/**
 * Filter DTO for ProductList list query.
 *
 * Generated code - do not modify directly.
 */
readonly class ProductListFilter
{
    public function __construct(
        public ?string $taxClass = null,
        public ?int $isActive = null,
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}

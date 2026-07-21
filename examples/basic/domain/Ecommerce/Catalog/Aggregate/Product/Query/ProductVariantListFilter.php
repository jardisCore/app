<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Query;

/**
 * Filter DTO for ProductVariantList list query.
 *
 * Generated code - do not modify directly.
 */
readonly class ProductVariantListFilter
{
    public function __construct(
        public string $productSku,
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}

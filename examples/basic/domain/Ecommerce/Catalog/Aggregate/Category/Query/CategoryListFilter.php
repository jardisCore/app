<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Query;

/**
 * Filter DTO for CategoryList list query.
 *
 * Generated code - do not modify directly.
 */
readonly class CategoryListFilter
{
    public function __construct(
        public ?int $isActive = null,
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}

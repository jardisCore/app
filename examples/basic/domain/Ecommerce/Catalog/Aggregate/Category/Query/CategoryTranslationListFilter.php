<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Query;

/**
 * Filter DTO for CategoryTranslationList list query.
 *
 * Generated code - do not modify directly.
 */
readonly class CategoryTranslationListFilter
{
    public function __construct(
        public string $categorySlug,
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Query\Handler;

use Ecommerce\Catalog\Aggregate\Category\Query\CategoryTranslationListFilter;
use Ecommerce\Catalog\Aggregate\Category\Repository\QueryCategoryTranslationList;
use Ecommerce\EcommerceContext;
use Throwable;

/**
 * Handler for CategoryTranslationList list query.
 *
 * Generated code - do not modify directly.
 */
class GetCategoryTranslationListHandler extends EcommerceContext
{
    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function __invoke(): array
    {
        /** @var CategoryTranslationListFilter $filter */
        $filter = $this->payload();

        $rows = $this->handle(QueryCategoryTranslationList::class)($filter);
        $total = (int) ($rows[0]['total_count'] ?? 0);

        return [
            'items' => array_map(fn(array $row) => array_diff_key($row, ['total_count' => true]), $rows),
            'total' => $total,
            'limit' => $filter->limit,
            'offset' => $filter->offset,
        ];
    }
}

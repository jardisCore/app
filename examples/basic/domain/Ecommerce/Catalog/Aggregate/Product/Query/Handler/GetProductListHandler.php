<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Query\Handler;

use Ecommerce\Catalog\Aggregate\Product\Query\ProductListFilter;
use Ecommerce\Catalog\Aggregate\Product\Repository\QueryProductList;
use Ecommerce\EcommerceContext;
use Throwable;

/**
 * Handler for ProductList list query.
 *
 * Generated code - do not modify directly.
 */
class GetProductListHandler extends EcommerceContext
{
    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function __invoke(): array
    {
        /** @var ProductListFilter $filter */
        $filter = $this->payload();

        $rows = $this->handle(QueryProductList::class)($filter);
        $total = (int) ($rows[0]['total_count'] ?? 0);

        return [
            'items' => array_map(fn(array $row) => array_diff_key($row, ['total_count' => true]), $rows),
            'total' => $total,
            'limit' => $filter->limit,
            'offset' => $filter->offset,
        ];
    }
}

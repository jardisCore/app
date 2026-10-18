<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Query\Handler;

use Ecommerce\Catalog\Model\Product\Query\ProductListFilter;
use Ecommerce\Catalog\Model\Product\Repository\QueryProductList;
use Ecommerce\EcommerceContext;
use JardisSupport\Data\Handler\ProjectWire;
use JardisSupport\Workflow\Data\ReadResult;
use Throwable;

/**
 * Handler for ProductList list query.
 *
 * Generated code - do not modify directly.
 */
class GetProductListHandler extends EcommerceContext
{
    /**
     * @return ReadResult<array<string, mixed>>
     * @throws Throwable
     */
    public function __invoke(): ReadResult
    {
        /** @var ProductListFilter $filter */
        $filter = $this->payload();

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->handle(QueryProductList::class)($filter);
        $total = (int) ($rows[0]['total_count'] ?? 0);

        $wireTypes = [
            'createdAt' => 'date-time',
            'updatedAt' => 'date-time',
        ];
        $projectWire = $this->handle(ProjectWire::class);

        /** @var list<array<string, mixed>> $items */
        $items = array_map(
            fn(array $row) => $projectWire(array_diff_key($row, ['total_count' => true]), $wireTypes),
            $rows
        );

        return ReadResult::ofList($items, $total);
    }
}

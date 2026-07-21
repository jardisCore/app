<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Query\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Order\Query\OrderItemListFilter;
use Ecommerce\Sales\Aggregate\Order\Repository\QueryOrderItemList;
use Throwable;

/**
 * Handler for OrderItemList list query.
 *
 * Generated code - do not modify directly.
 */
class GetOrderItemListHandler extends EcommerceContext
{
    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function __invoke(): array
    {
        /** @var OrderItemListFilter $filter */
        $filter = $this->payload();

        $rows = $this->handle(QueryOrderItemList::class)($filter);
        $total = (int) ($rows[0]['total_count'] ?? 0);

        return [
            'items' => array_map(fn(array $row) => array_diff_key($row, ['total_count' => true]), $rows),
            'total' => $total,
            'limit' => $filter->limit,
            'offset' => $filter->offset,
        ];
    }
}

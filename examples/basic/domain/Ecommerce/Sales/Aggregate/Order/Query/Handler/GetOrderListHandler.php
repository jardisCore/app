<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Query\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Order\Query\OrderListFilter;
use Ecommerce\Sales\Aggregate\Order\Repository\QueryOrderList;
use Throwable;

/**
 * Handler for OrderList list query.
 *
 * Generated code - do not modify directly.
 */
class GetOrderListHandler extends EcommerceContext
{
    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function __invoke(): array
    {
        /** @var OrderListFilter $filter */
        $filter = $this->payload();

        $rows = $this->handle(QueryOrderList::class)($filter);
        $total = (int) ($rows[0]['total_count'] ?? 0);

        return [
            'items' => array_map(fn(array $row) => array_diff_key($row, ['total_count' => true]), $rows),
            'total' => $total,
            'limit' => $filter->limit,
            'offset' => $filter->offset,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Query\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Order\Query\OrderDetailListFilter;
use Ecommerce\Sales\Aggregate\Order\Repository\QueryOrderDetailList;
use Throwable;

/**
 * Handler for OrderDetailList list query.
 *
 * Generated code - do not modify directly.
 */
class GetOrderDetailListHandler extends EcommerceContext
{
    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function __invoke(): array
    {
        /** @var OrderDetailListFilter $filter */
        $filter = $this->payload();

        $rows = $this->handle(QueryOrderDetailList::class)($filter);
        $total = (int) ($rows[0]['total_count'] ?? 0);

        return [
            'items' => array_map(fn(array $row) => array_diff_key($row, ['total_count' => true]), $rows),
            'total' => $total,
            'limit' => $filter->limit,
            'offset' => $filter->offset,
        ];
    }
}

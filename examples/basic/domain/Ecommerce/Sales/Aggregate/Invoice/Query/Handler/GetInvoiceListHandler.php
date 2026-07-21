<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Query\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Invoice\Query\InvoiceListFilter;
use Ecommerce\Sales\Aggregate\Invoice\Repository\QueryInvoiceList;
use Throwable;

/**
 * Handler for InvoiceList list query.
 *
 * Generated code - do not modify directly.
 */
class GetInvoiceListHandler extends EcommerceContext
{
    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function __invoke(): array
    {
        /** @var InvoiceListFilter $filter */
        $filter = $this->payload();

        $rows = $this->handle(QueryInvoiceList::class)($filter);
        $total = (int) ($rows[0]['total_count'] ?? 0);

        return [
            'items' => array_map(fn(array $row) => array_diff_key($row, ['total_count' => true]), $rows),
            'total' => $total,
            'limit' => $filter->limit,
            'offset' => $filter->offset,
        ];
    }
}

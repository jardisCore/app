<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Query\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Invoice\Query\InvoiceLineListFilter;
use Ecommerce\Sales\Aggregate\Invoice\Repository\QueryInvoiceLineList;
use Throwable;

/**
 * Handler for InvoiceLineList list query.
 *
 * Generated code - do not modify directly.
 */
class GetInvoiceLineListHandler extends EcommerceContext
{
    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function __invoke(): array
    {
        /** @var InvoiceLineListFilter $filter */
        $filter = $this->payload();

        $rows = $this->handle(QueryInvoiceLineList::class)($filter);
        $total = (int) ($rows[0]['total_count'] ?? 0);

        return [
            'items' => array_map(fn(array $row) => array_diff_key($row, ['total_count' => true]), $rows),
            'total' => $total,
            'limit' => $filter->limit,
            'offset' => $filter->offset,
        ];
    }
}

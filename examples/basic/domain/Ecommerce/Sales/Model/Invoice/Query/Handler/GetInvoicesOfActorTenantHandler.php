<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Query\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Invoice\Query\InvoicesOfActorTenantFilter;
use Ecommerce\Sales\Model\Invoice\Repository\QueryInvoicesOfActorTenant;
use JardisSupport\Data\Handler\ProjectWire;
use JardisSupport\Response\Data\ReadResult;
use Throwable;

/**
 * Handler for InvoicesOfActorTenant list query.
 *
 * Generated code - do not modify directly.
 */
class GetInvoicesOfActorTenantHandler extends EcommerceContext
{
    /**
     * @return ReadResult<array<string, mixed>>
     * @throws Throwable
     */
    public function __invoke(): ReadResult
    {
        /** @var InvoicesOfActorTenantFilter $filter */
        $filter = $this->payload();

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->handle(QueryInvoicesOfActorTenant::class)($filter);
        $total = (int) ($rows[0]['total_count'] ?? 0);

        $wireTypes = [
            'issuedAt' => 'date-time',
            'dueAt' => 'date-time',
            'paidAt' => 'date-time',
            'createdAt' => 'date-time',
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

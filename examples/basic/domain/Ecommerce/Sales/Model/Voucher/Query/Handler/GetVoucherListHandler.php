<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Query\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Voucher\Query\VoucherListFilter;
use Ecommerce\Sales\Model\Voucher\Repository\QueryVoucherList;
use JardisSupport\Data\Handler\ProjectWire;
use JardisSupport\Workflow\Data\ReadResult;
use Throwable;

/**
 * Handler for VoucherList list query.
 *
 * Generated code - do not modify directly.
 */
class GetVoucherListHandler extends EcommerceContext
{
    /**
     * @return ReadResult<array<string, mixed>>
     * @throws Throwable
     */
    public function __invoke(): ReadResult
    {
        /** @var VoucherListFilter $filter */
        $filter = $this->payload();

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->handle(QueryVoucherList::class)($filter);
        $total = (int) ($rows[0]['total_count'] ?? 0);

        $wireTypes = [
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

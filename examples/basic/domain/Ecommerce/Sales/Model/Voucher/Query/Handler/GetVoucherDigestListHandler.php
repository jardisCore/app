<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Query\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Voucher\Query\VoucherDigestListFilter;
use Ecommerce\Sales\Model\Voucher\Repository\QueryVoucherDigestList;
use JardisSupport\Data\Handler\ProjectWire;
use JardisSupport\Workflow\Data\ReadResult;
use Throwable;

/**
 * Handler for VoucherDigestList list query.
 *
 * Generated code - do not modify directly.
 */
class GetVoucherDigestListHandler extends EcommerceContext
{
    /**
     * @return ReadResult<array<string, mixed>>
     * @throws Throwable
     */
    public function __invoke(): ReadResult
    {
        /** @var VoucherDigestListFilter $filter */
        $filter = $this->payload();

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->handle(QueryVoucherDigestList::class)($filter);
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

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Query\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Voucher\Query\VoucherChannelKeyListFilter;
use Ecommerce\Sales\Model\Voucher\Repository\QueryVoucherChannelKeyList;
use JardisSupport\Workflow\Data\ReadResult;
use Throwable;

/**
 * Handler for VoucherChannelKeyList list query.
 *
 * Generated code - do not modify directly.
 */
class GetVoucherChannelKeyListHandler extends EcommerceContext
{
    /**
     * @return ReadResult<array<string, mixed>>
     * @throws Throwable
     */
    public function __invoke(): ReadResult
    {
        /** @var VoucherChannelKeyListFilter $filter */
        $filter = $this->payload();

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->handle(QueryVoucherChannelKeyList::class)($filter);
        $total = (int) ($rows[0]['total_count'] ?? 0);

        /** @var list<array<string, mixed>> $items */
        $items = array_map(fn(array $row) => array_diff_key($row, ['total_count' => true]), $rows);

        return ReadResult::ofList($items, $total);
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Query\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Aggregate\Shipment\Query\ShipmentTrackingListFilter;
use Ecommerce\Fulfillment\Aggregate\Shipment\Repository\QueryShipmentTrackingList;
use Throwable;

/**
 * Handler for ShipmentTrackingList list query.
 *
 * Generated code - do not modify directly.
 */
class GetShipmentTrackingListHandler extends EcommerceContext
{
    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     * @throws Throwable
     */
    public function __invoke(): array
    {
        /** @var ShipmentTrackingListFilter $filter */
        $filter = $this->payload();

        $rows = $this->handle(QueryShipmentTrackingList::class)($filter);
        $total = (int) ($rows[0]['total_count'] ?? 0);

        return [
            'items' => array_map(fn(array $row) => array_diff_key($row, ['total_count' => true]), $rows),
            'total' => $total,
            'limit' => $filter->limit,
            'offset' => $filter->offset,
        ];
    }
}

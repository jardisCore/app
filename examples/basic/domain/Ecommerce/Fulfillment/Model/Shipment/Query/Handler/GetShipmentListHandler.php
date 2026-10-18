<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Query\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Query\ShipmentListFilter;
use Ecommerce\Fulfillment\Model\Shipment\Repository\QueryShipmentList;
use JardisSupport\Data\Handler\ProjectWire;
use JardisSupport\Workflow\Data\ReadResult;
use Throwable;

/**
 * Handler for ShipmentList list query.
 *
 * Generated code - do not modify directly.
 */
class GetShipmentListHandler extends EcommerceContext
{
    /**
     * @return ReadResult<array<string, mixed>>
     * @throws Throwable
     */
    public function __invoke(): ReadResult
    {
        /** @var ShipmentListFilter $filter */
        $filter = $this->payload();

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->handle(QueryShipmentList::class)($filter);
        $total = (int) ($rows[0]['total_count'] ?? 0);

        $wireTypes = [
            'estimatedDelivery' => 'date',
            'shippedAt' => 'date-time',
            'deliveredAt' => 'date-time',
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

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Query\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Query\OrdersOfActorFilter;
use Ecommerce\Sales\Model\Order\Repository\QueryOrdersOfActor;
use JardisSupport\Data\Handler\ProjectWire;
use JardisSupport\Response\Data\ReadResult;
use Throwable;

/**
 * Handler for OrdersOfActor list query.
 *
 * Generated code - do not modify directly.
 */
class GetOrdersOfActorHandler extends EcommerceContext
{
    /**
     * @return ReadResult<array<string, mixed>>
     * @throws Throwable
     */
    public function __invoke(): ReadResult
    {
        /** @var OrdersOfActorFilter $filter */
        $filter = $this->payload();

        /** @var list<array<string, mixed>> $rows */
        $rows = $this->handle(QueryOrdersOfActor::class)($filter);
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

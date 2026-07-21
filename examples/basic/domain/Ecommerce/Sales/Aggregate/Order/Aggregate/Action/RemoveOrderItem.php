<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Aggregate\Action;

use Ecommerce\EcommerceContext;
use RuntimeException;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Entity\Order as OrderAggregate;

/**
 * Action: RemoveOrderItem
 */
class RemoveOrderItem extends EcommerceContext
{
    /**
     * Removes an OrderItem from the orderItem collection.
     *
     * @param OrderAggregate $aggregate
     * @param int $id
     * @return array<array{string, object|null}> Entities to track for deletion
     * @throws RuntimeException
     */
    public function __invoke(OrderAggregate $aggregate, int $id): array
    {
        $removals = [];
        $collection = $aggregate->getOrderItem();

        foreach ($collection as $item) {
            $primaryKey = $item::PRIMARY_KEY;
            $getter = 'get' . ucfirst($primaryKey);
            $itemId = $item->$getter();
            if ($itemId !== null && $itemId === $id) {
                if (count($collection) <= 1) {
                    throw new RuntimeException('Cannot remove the last OrderItem: at least one is required.');
                }
                $removals[] = ['orderItem', $item];
                $aggregate->removeOrderItem($item);
                break;
            }
        }
        return $removals;
    }
}

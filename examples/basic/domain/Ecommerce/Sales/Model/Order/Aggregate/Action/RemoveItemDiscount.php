<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Aggregate\Action;

use Ecommerce\EcommerceContext;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Ecommerce\Sales\Model\Order\Aggregate\Entity\Order as OrderAggregate;

/**
 * Action: RemoveItemDiscount
 */
class RemoveItemDiscount extends EcommerceContext
{
    /**
     * Removes an ItemDiscount from the itemDiscount collection.
     *
     * @param OrderAggregate $aggregate
     * @param int $id
     * @return array<array{string, object|null}>|ResponseStatus Entities to track for deletion, or NotFound when a path
     *     level is absent
     */
    public function __invoke(OrderAggregate $aggregate, int $id): array|ResponseStatus
    {
        $removals = [];
        foreach ($aggregate->getOrderItem() as $parent) {
            $collection = $parent->getItemDiscount();
            foreach ($collection as $item) {
                $primaryKey = $item::PRIMARY_KEY;
                $itemGetter = 'get' . ucfirst($primaryKey);
                $itemId = $item->$itemGetter();
                if ($itemId !== null && $itemId === $id) {
                    $removals[] = ['itemDiscount', $item];
                    $parent->removeItemDiscount($item);
                    return $removals;
                }
            }
        }
        return ResponseStatus::NotFound;
    }
}

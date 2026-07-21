<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Action;

use Ecommerce\EcommerceContext;
use RuntimeException;
use Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Entity\Shipment as ShipmentAggregate;

/**
 * Action: RemoveShipmentItem
 */
class RemoveShipmentItem extends EcommerceContext
{
    /**
     * Removes a ShipmentItem from the shipmentItem collection.
     *
     * @param ShipmentAggregate $aggregate
     * @param int $id
     * @return array<array{string, object|null}> Entities to track for deletion
     * @throws RuntimeException
     */
    public function __invoke(ShipmentAggregate $aggregate, int $id): array
    {
        $removals = [];
        $collection = $aggregate->getShipmentItem();

        foreach ($collection as $item) {
            $primaryKey = $item::PRIMARY_KEY;
            $getter = 'get' . ucfirst($primaryKey);
            $itemId = $item->$getter();
            if ($itemId !== null && $itemId === $id) {
                if (count($collection) <= 1) {
                    throw new RuntimeException('Cannot remove the last ShipmentItem: at least one is required.');
                }
                $removals[] = ['shipmentItem', $item];
                $aggregate->removeShipmentItem($item);
                break;
            }
        }
        return $removals;
    }
}

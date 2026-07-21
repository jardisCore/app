<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Entity\Shipment as ShipmentAggregate;

/**
 * Action: RemoveTrackingEvent
 */
class RemoveTrackingEvent extends EcommerceContext
{
    /**
     * Removes a TrackingEvent from the trackingEvent collection.
     *
     * @param ShipmentAggregate $aggregate
     * @param int $id
     * @return array<array{string, object|null}> Entities to track for deletion
     */
    public function __invoke(ShipmentAggregate $aggregate, int $id): array
    {
        $removals = [];
        $collection = $aggregate->getTrackingEvent();

        foreach ($collection as $item) {
            $primaryKey = $item::PRIMARY_KEY;
            $getter = 'get' . ucfirst($primaryKey);
            $itemId = $item->$getter();
            if ($itemId !== null && $itemId === $id) {
                $removals[] = ['trackingEvent', $item];
                $aggregate->removeTrackingEvent($item);
                break;
            }
        }
        return $removals;
    }
}

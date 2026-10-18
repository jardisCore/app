<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Aggregate\Action;

use Ecommerce\EcommerceContext;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Fulfillment\Model\Shipment\Aggregate\Entity\Shipment as ShipmentAggregate;

/**
 * Action: UpdateTrackingEvent
 */
class UpdateTrackingEvent extends EcommerceContext
{
    /**
     * Updates a TrackingEvent in the trackingEvent collection.
     *
     * @param ShipmentAggregate $aggregate
     * @param int $id
     * @param array<string, mixed> $data Entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(ShipmentAggregate $aggregate, int $id, array $data): void
    {
        $hydration = $this->handle(Hydration::class);

        foreach ($aggregate->getTrackingEvent() as $item) {
            $primaryKey = $item::PRIMARY_KEY;
            $getter = 'get' . ucfirst($primaryKey);
            $itemId = $item->$getter();
            if ($itemId !== null && $itemId === $id) {
                // apply() sets the values WITHOUT updating the snapshot: change tracking and the
                // expected-values check (stale write -> 409) stay in effect.
                $hydration->apply($item, $data);
                break;
            }
        }
    }
}

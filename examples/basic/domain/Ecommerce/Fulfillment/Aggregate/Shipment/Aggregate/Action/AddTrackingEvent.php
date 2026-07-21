<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Entity\TrackingEvent;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Entity\Shipment as ShipmentAggregate;

/**
 * Action: AddTrackingEvent
 */
class AddTrackingEvent extends EcommerceContext
{
    /**
     * Adds a TrackingEvent to the trackingEvent collection.
     *
     * @param ShipmentAggregate $aggregate
     * @param array<string, mixed> $data Entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(ShipmentAggregate $aggregate, array $data): void
    {
        $hydration = $this->handle(Hydration::class);

        $entity = $hydration->hydrate($this->handle(TrackingEvent::class), $data);

        $aggregate->addTrackingEvent($entity);
    }
}

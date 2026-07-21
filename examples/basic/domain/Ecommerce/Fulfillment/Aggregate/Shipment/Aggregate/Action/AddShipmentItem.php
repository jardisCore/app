<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Entity\ShipmentItem;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Entity\Shipment as ShipmentAggregate;

/**
 * Action: AddShipmentItem
 */
class AddShipmentItem extends EcommerceContext
{
    /**
     * Adds a ShipmentItem to the shipmentItem collection.
     *
     * @param ShipmentAggregate $aggregate
     * @param array<string, mixed> $data Entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(ShipmentAggregate $aggregate, array $data): void
    {
        $hydration = $this->handle(Hydration::class);

        $entity = $hydration->hydrate($this->handle(ShipmentItem::class), $data);

        $aggregate->addShipmentItem($entity);
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Entity\ShipmentAddress;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Entity\Shipment as ShipmentAggregate;

/**
 * Action: SetShipmentAddress
 */
class SetShipmentAddress extends EcommerceContext
{
    /**
     * Sets/replaces the shipmentAddress entity.
     *
     * @param ShipmentAggregate $aggregate
     * @param array<string, mixed> $data Entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(ShipmentAggregate $aggregate, array $data): void
    {
        $hydration = $this->handle(Hydration::class);
        $entity = $aggregate->getShipmentAddress() ?? $this->handle(ShipmentAddress::class);
        $hydration->apply($entity, $data);
        $aggregate->setShipmentAddress($entity);
    }
}

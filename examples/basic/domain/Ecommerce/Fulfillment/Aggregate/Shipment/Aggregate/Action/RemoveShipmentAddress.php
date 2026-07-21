<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Entity\Shipment as ShipmentAggregate;

/**
 * Action: RemoveShipmentAddress
 */
class RemoveShipmentAddress extends EcommerceContext
{
    /**
     * Removes the shipmentAddress entity.
     *
     * @param ShipmentAggregate $aggregate
     * @return array<array{string, object|null}> Entities to track for deletion
     */
    public function __invoke(ShipmentAggregate $aggregate): array
    {
        $removals = [];
        $existing = $aggregate->getShipmentAddress();
        if ($existing !== null) {
            $pkGetter = 'get' . ucfirst($existing::PRIMARY_KEY);
            if ($existing->$pkGetter() !== null) {
                $aggregate->setDeliveryAddressId(null);
                $removals[] = ['shipmentAddress', $existing];
            }
        }
        $aggregate->setShipmentAddress(null);
        return $removals;
    }
}

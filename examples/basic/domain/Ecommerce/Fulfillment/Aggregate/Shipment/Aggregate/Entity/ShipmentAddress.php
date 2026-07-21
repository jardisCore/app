<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Entity;

use JardisSupport\Data\Attribute\Aggregate;
use Ecommerce\Fulfillment\Entity\ShipmentAddress as EntityShipmentAddress;

/**
 * Aggregate entity: ShipmentAddress
 *
 * Extends base entity with aggregate-specific relationships.
 */
#[Aggregate(name: 'ShipmentAddress')]
class ShipmentAddress extends EntityShipmentAddress
{
}

<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Entity;

use JardisSupport\Data\Attribute\Aggregate;
use Ecommerce\Fulfillment\Entity\ShipmentItem as EntityShipmentItem;

/**
 * Aggregate entity: ShipmentItem
 *
 * Extends base entity with aggregate-specific relationships.
 */
#[Aggregate(name: 'ShipmentItem')]
class ShipmentItem extends EntityShipmentItem
{
}

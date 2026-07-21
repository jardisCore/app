<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Entity;

use JardisSupport\Data\Attribute\Aggregate;
use Ecommerce\Fulfillment\Entity\TrackingEvent as EntityTrackingEvent;

/**
 * Aggregate entity: TrackingEvent
 *
 * Extends base entity with aggregate-specific relationships.
 */
#[Aggregate(name: 'TrackingEvent')]
class TrackingEvent extends EntityTrackingEvent
{
}

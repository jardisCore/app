<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Event;

use DateTimeImmutable;

/**
 * Event: TrackingEvent was removed.
 *
 * Dispatched after a TrackingEvent is removed from the aggregate.
 */
readonly class ShipmentTrackingEventRemoved
{
    /**
     * @param string $shipmentIdentifier
     * @param int $trackingEventId
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $shipmentIdentifier,
        public int $trackingEventId,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

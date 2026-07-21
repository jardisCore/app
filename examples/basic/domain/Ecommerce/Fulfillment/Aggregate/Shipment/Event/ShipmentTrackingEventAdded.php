<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Event;

use DateTimeImmutable;

/**
 * Event: TrackingEvent was added.
 *
 * Dispatched after a new TrackingEvent is added to the aggregate.
 */
readonly class ShipmentTrackingEventAdded
{
    /**
     * @param string $shipmentIdentifier
     * @param int $trackingEventId
     * @param string $eventCode
     * @param string $status
     * @param ?string $location
     * @param ?string $postalCode
     * @param ?string $detail
     * @param DateTimeImmutable $reportedAt
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $shipmentIdentifier,
        public int $trackingEventId,
        public string $eventCode,
        public string $status,
        public ?string $location,
        public ?string $postalCode,
        public ?string $detail,
        public DateTimeImmutable $reportedAt,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

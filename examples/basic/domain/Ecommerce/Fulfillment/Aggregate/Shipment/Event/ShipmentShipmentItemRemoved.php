<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Event;

use DateTimeImmutable;

/**
 * Event: ShipmentItem was removed.
 *
 * Dispatched after a ShipmentItem is removed from the aggregate.
 */
readonly class ShipmentShipmentItemRemoved
{
    /**
     * @param string $shipmentIdentifier
     * @param string $shipmentItemIdentifier
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $shipmentIdentifier,
        public string $shipmentItemIdentifier,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

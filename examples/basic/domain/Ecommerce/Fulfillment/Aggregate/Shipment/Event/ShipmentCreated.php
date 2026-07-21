<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Event;

use DateTimeImmutable;

/**
 * Event: Shipment was created.
 *
 * Dispatched after a new aggregate is successfully persisted.
 */
readonly class ShipmentCreated
{
    /**
     * @param string $shipmentIdentifier
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $shipmentIdentifier,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

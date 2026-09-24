<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Event;

use DateTimeImmutable;

/**
 * Event: Shipment was removed.
 *
 * Dispatched after an aggregate is successfully deleted.
 */
readonly class ShipmentRemoved
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

<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Command;

/**
 * Command data class for RemoveTrackingEvent.
 *
 * Readonly DTO for command operations.
 */
readonly class RemoveTrackingEvent
{
    public function __construct(
        public string $shipmentIdentifier,
        public int $trackingEventId
    ) {
    }
}

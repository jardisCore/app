<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command;

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

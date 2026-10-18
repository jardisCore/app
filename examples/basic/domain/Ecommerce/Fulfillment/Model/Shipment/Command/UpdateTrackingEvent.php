<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command;

use DateTimeImmutable;

/**
 * Command data class for UpdateTrackingEvent.
 *
 * Readonly DTO for command operations.
 */
readonly class UpdateTrackingEvent
{
    public function __construct(
        public string $shipmentIdentifier,
        public int $trackingEventId,
        public string $eventCode,
        public string $status,
        public ?string $location,
        public ?string $postalCode,
        public ?string $detail,
        public DateTimeImmutable $occurredAt,
        public DateTimeImmutable $reportedAt
    ) {
    }
}

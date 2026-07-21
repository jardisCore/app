<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Command;

use DateTimeImmutable;

/**
 * Command data class for TrackingEvent.
 *
 * Readonly DTO for command operations.
 */
readonly class TrackingEvent
{
    public function __construct(
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

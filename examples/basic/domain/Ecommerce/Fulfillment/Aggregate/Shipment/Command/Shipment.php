<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Command;

use DateTimeImmutable;

/**
 * Command data class for Shipment.
 *
 * Readonly DTO for command operations.
 */
readonly class Shipment
{
    public function __construct(
        public string $orderNumber,
        public string $customerIdentifier,
        public string $carrier,
        public string $serviceLevel,
        public ?string $trackingNumber,
        public string $status,
        public ?int $weightGrams,
        public int $packageCount,
        public ?float $insuranceValue,
        public ?DateTimeImmutable $estimatedDelivery,
        public ?DateTimeImmutable $shippedAt,
        public ?DateTimeImmutable $deliveredAt,
        public ?string $note,
        public ShipmentAddress $shipmentAddress,
        /** @var array<ShipmentItem> */
        public array $shipmentItem,
        /** @var array<TrackingEvent> */
        public array $trackingEvent = []
    ) {
    }
}

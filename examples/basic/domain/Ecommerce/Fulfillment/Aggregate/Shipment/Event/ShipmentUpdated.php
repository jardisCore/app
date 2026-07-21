<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Event;

use DateTimeImmutable;

/**
 * Event: Shipment was updated.
 *
 * Dispatched after Shipment fields are successfully persisted.
 */
readonly class ShipmentUpdated
{
    /**
     * @param string $shipmentIdentifier
     * @param string $orderNumber
     * @param string $customerIdentifier
     * @param string $carrier
     * @param string $serviceLevel
     * @param ?string $trackingNumber
     * @param string $status
     * @param ?int $weightGrams
     * @param int $packageCount
     * @param ?float $insuranceValue
     * @param ?DateTimeImmutable $estimatedDelivery
     * @param ?DateTimeImmutable $shippedAt
     * @param ?DateTimeImmutable $deliveredAt
     * @param ?string $note
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $shipmentIdentifier,
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
        public DateTimeImmutable $occurredAt
    ) {
    }
}

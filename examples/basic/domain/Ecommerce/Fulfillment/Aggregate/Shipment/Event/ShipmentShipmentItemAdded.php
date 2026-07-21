<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Event;

use DateTimeImmutable;

/**
 * Event: ShipmentItem was added.
 *
 * Dispatched after a new ShipmentItem is added to the aggregate.
 */
readonly class ShipmentShipmentItemAdded
{
    /**
     * @param string $shipmentIdentifier
     * @param string $shipmentItemIdentifier
     * @param string $productSku
     * @param string $productName
     * @param int $quantity
     * @param ?int $weightGrams
     * @param int $isFragile
     * @param ?string $serialNumber
     * @param ?string $lotNumber
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $shipmentIdentifier,
        public string $shipmentItemIdentifier,
        public string $productSku,
        public string $productName,
        public int $quantity,
        public ?int $weightGrams,
        public int $isFragile,
        public ?string $serialNumber,
        public ?string $lotNumber,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

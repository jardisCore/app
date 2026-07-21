<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Command;

/**
 * Command data class for AddShipmentItem.
 *
 * Readonly DTO for command operations.
 */
readonly class AddShipmentItem
{
    public function __construct(
        public string $shipmentIdentifier,
        public string $productSku,
        public string $productName,
        public int $quantity,
        public ?int $weightGrams,
        public int $isFragile,
        public ?string $serialNumber,
        public ?string $lotNumber
    ) {
    }
}

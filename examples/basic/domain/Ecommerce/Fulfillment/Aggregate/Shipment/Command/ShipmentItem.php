<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Command;

/**
 * Command data class for ShipmentItem.
 *
 * Readonly DTO for command operations.
 */
readonly class ShipmentItem
{
    public function __construct(
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

<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command;

/**
 * Command data class for UpdateShipmentItem.
 *
 * Readonly DTO for command operations.
 */
readonly class UpdateShipmentItem
{
    public function __construct(
        public string $tenant,
        public string $shipmentIdentifier,
        public string $shipmentItemIdentifier,
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

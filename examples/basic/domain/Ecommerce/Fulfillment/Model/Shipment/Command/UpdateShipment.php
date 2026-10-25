<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command;

use DateTimeImmutable;

/**
 * Command data class for UpdateShipment.
 *
 * Readonly DTO for command operations.
 */
readonly class UpdateShipment
{
    public function __construct(
        public string $tenant,
        public string $shipmentIdentifier,
        public string $orderNumber,
        public string $customerIdentifier,
        public string $carrier,
        public string $serviceLevel,
        public ?string $trackingNumber,
        public string $status,
        public ?int $weightGrams,
        public ?string $insuranceValue,
        public ?DateTimeImmutable $estimatedDelivery,
        public ?DateTimeImmutable $shippedAt,
        public ?DateTimeImmutable $deliveredAt,
        public ?string $note
    ) {
    }
}

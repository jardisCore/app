<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Query;

/**
 * Query data class for ShipmentListFilter.
 *
 * Readonly DTO for query operations.
 */
readonly class ShipmentListFilter
{
    public function __construct(
        public ?int $id = null,
        public ?string $identifier = null,
        public ?string $orderNumber = null,
        public ?string $customerIdentifier = null,
        public ?int $deliveryAddressId = null,
        public ?string $carrier = null,
        public ?string $serviceLevel = null,
        public ?string $trackingNumber = null,
        public ?string $status = null,
        public ?int $weightGrams = null,
        public ?int $packageCount = null,
        public ?float $insuranceValue = null,
        public ?string $estimatedDeliveryFrom = null,
        public ?string $estimatedDeliveryUntil = null,
        public ?string $shippedAtFrom = null,
        public ?string $shippedAtUntil = null,
        public ?string $deliveredAtFrom = null,
        public ?string $deliveredAtUntil = null,
        public ?string $note = null,
        public ?string $createdAtFrom = null,
        public ?string $createdAtUntil = null,
        public int $limit = 20,
        public int $offset = 0
    ) {
    }
}

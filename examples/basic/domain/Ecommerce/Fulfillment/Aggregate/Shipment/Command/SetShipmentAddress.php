<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Command;

/**
 * Command data class for SetShipmentAddress.
 *
 * Readonly DTO for command operations.
 */
readonly class SetShipmentAddress
{
    public function __construct(
        public string $shipmentIdentifier,
        public string $recipientName,
        public ?string $company,
        public string $street,
        public ?string $street2,
        public string $city,
        public string $postalCode,
        public ?string $state,
        public string $country,
        public ?string $phone
    ) {
    }
}

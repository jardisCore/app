<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Event;

use DateTimeImmutable;

/**
 * Event: ShipmentAddress was updated.
 *
 * Dispatched after ShipmentAddress fields are successfully persisted.
 */
readonly class ShipmentShipmentAddressUpdated
{
    /**
     * @param string $shipmentIdentifier
     * @param string $recipientName
     * @param ?string $company
     * @param string $street
     * @param ?string $street2
     * @param string $city
     * @param string $postalCode
     * @param ?string $state
     * @param string $country
     * @param ?string $phone
     * @param DateTimeImmutable $occurredAt
     */
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
        public ?string $phone,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

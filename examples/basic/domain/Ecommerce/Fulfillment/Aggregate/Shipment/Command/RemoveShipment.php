<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Command;

/**
 * Command data class for RemoveShipment.
 *
 * Readonly DTO for command operations.
 */
readonly class RemoveShipment
{
    public function __construct(
        public string $shipmentIdentifier
    ) {
    }
}

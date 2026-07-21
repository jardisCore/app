<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Command;

/**
 * Command data class for RemoveShipmentItem.
 *
 * Readonly DTO for command operations.
 */
readonly class RemoveShipmentItem
{
    public function __construct(
        public string $shipmentIdentifier,
        public string $shipmentItemIdentifier
    ) {
    }
}

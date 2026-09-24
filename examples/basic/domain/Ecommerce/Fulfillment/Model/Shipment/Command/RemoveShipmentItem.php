<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command;

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

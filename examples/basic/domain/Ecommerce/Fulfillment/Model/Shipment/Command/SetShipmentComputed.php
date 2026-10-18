<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Command;

/**
 * Command data class for SetShipmentComputed.
 *
 * Readonly DTO for command operations.
 */
readonly class SetShipmentComputed
{
    public function __construct(
        public string $shipmentIdentifier,
        public int $packageCount
    ) {
    }
}

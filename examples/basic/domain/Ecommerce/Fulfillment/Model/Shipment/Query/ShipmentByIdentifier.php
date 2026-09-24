<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Query;

/**
 * Query data class for ShipmentByIdentifier.
 *
 * Readonly DTO for query operations.
 */
readonly class ShipmentByIdentifier
{
    public function __construct(
        public string $identifier
    ) {
    }
}

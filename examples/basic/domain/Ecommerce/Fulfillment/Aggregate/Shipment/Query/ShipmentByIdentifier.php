<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Query;

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

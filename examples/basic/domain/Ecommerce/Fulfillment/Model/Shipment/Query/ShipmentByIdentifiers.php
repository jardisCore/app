<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Query;

/**
 * Query data class for ShipmentByIdentifiers.
 *
 * Readonly DTO for query operations.
 */
readonly class ShipmentByIdentifiers
{
    public function __construct(
        public array $identifiers
    ) {
    }
}

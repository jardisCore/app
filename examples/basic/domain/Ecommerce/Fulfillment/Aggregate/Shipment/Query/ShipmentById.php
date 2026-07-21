<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Query;

/**
 * Query data class for ShipmentById.
 *
 * Readonly DTO for query operations.
 */
readonly class ShipmentById
{
    public function __construct(
        public int $id
    ) {
    }
}

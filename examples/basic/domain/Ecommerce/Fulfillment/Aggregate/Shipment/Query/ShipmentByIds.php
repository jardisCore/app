<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Query;

/**
 * Query data class for ShipmentByIds.
 *
 * Readonly DTO for query operations.
 */
readonly class ShipmentByIds
{
    public function __construct(
        public array $ids
    ) {
    }
}

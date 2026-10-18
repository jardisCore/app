<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Query;

/**
 * Query data class for ShipmentByOrderNumberFilter.
 *
 * Readonly DTO for query operations.
 */
readonly class ShipmentByOrderNumberFilter
{
    public function __construct(
        public string $orderNumber,
        public int $limit = 50,
        public int $offset = 0
    ) {
    }
}

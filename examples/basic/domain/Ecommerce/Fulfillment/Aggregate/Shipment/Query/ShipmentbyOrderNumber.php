<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Query;

/**
 * Query data class for ShipmentbyOrderNumber.
 *
 * Readonly DTO for query operations.
 */
readonly class ShipmentbyOrderNumber
{
    public function __construct(
        public string $orderNumber
    ) {
    }
}

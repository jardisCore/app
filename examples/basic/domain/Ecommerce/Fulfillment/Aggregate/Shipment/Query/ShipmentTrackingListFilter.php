<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Query;

/**
 * Filter DTO for ShipmentTrackingList list query.
 *
 * Generated code - do not modify directly.
 */
readonly class ShipmentTrackingListFilter
{
    public function __construct(
        public string $shipmentIdentifier,
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment\Query;

/**
 * Filter DTO for ShipmentList list query.
 *
 * Generated code - do not modify directly.
 */
readonly class ShipmentListFilter
{
    public function __construct(
        public ?string $status = null,
        public ?string $carrier = null,
        public ?string $orderNumber = null,
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment;

use Throwable;
use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Aggregate\Shipment\ShipmentRead;

/**
 * Fulfillment Bounded Context.
 *
 * Public API (the "Außentür", G2): one read accessor per aggregate
 * plus process(). Aggregate writes are not exposed here.
 *
 * Usage:
 *   $bc->shipment()->{useCase}(...)
 */
class Fulfillment extends EcommerceContext
{
    /**
     * Returns the Shipment aggregate read facade.
     *
     * @return ShipmentRead
     * @throws Throwable
     */
    public function shipment(): ShipmentRead
    {
        return $this->handle(ShipmentRead::class);
    }
}

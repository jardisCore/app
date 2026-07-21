<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Query\Response;

/**
 * Read projection for ShipmentItemResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class ShipmentItemResponse
{
    public string $identifier = '';
    public string $productSku = '';
    public string $productName = '';
    public int $quantity = 0;
    public ?int $weightGrams = null;
    public int $isFragile = 0;
    public ?string $serialNumber = null;
    public ?string $lotNumber = null;
}

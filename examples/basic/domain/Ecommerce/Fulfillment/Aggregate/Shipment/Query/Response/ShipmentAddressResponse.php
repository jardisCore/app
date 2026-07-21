<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Query\Response;

/**
 * Read projection for ShipmentAddressResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class ShipmentAddressResponse
{
    public string $recipientName = '';
    public ?string $company = null;
    public string $street = '';
    public ?string $street2 = null;
    public string $city = '';
    public string $postalCode = '';
    public ?string $state = null;
    public string $country = '';
    public ?string $phone = null;
}

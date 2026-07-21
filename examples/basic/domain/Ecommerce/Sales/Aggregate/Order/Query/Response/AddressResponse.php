<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Query\Response;

/**
 * Read projection for AddressResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class AddressResponse
{
    public string $street = '';
    public string $city = '';
    public string $postalCode = '';
    public string $country = '';
}

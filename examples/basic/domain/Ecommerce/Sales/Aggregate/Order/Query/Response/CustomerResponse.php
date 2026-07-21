<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Query\Response;

use DateTime;

/**
 * Read projection for CustomerResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class CustomerResponse
{
    public string $identifier = '';
    public string $email = '';
    public string $customerName = '';
    public ?string $phone = null;
    public ?DateTime $createdAt = null;
    public ?AddressResponse $address = null;
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Query\Response;

/**
 * Read projection for ItemDiscountResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class ItemDiscountResponse
{
    public int $id = 0;
    public string $discountCode = '';
    public float $amount = 0.0;
}

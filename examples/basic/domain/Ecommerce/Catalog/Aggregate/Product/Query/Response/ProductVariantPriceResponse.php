<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Query\Response;

use DateTime;

/**
 * Read projection for ProductVariantPriceResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class ProductVariantPriceResponse
{
    public int $id = 0;
    public string $region = '';
    public float $price = 0.0;
    public string $currency = '';
    public ?DateTime $validFrom = null;
    public ?DateTime $validUntil = null;
}

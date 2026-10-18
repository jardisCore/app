<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Query\Response;

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

    /** @var numeric-string */
    public string $price = '0';
    public string $currency = '';
    public ?string $validFrom = null;
    public ?string $validUntil = null;
}

<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Query\Response;

use DateTime;

/**
 * Read projection for ProductVariantResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class ProductVariantResponse
{
    public string $identifier = '';
    public string $sku = '';
    public string $variantName = '';
    public string $optionName = '';
    public string $optionValue = '';
    public float $priceModifier = 0.0;
    public int $stockQuantity = 0;
    public ?int $lowStockThreshold = null;
    public ?int $weightGrams = null;
    public int $isAvailable = 0;
    public int $sortOrder = 0;
    public ?DateTime $createdAt = null;

    /** @var ProductVariantPriceResponse[] */
    public array $productVariantPrice = [];
}

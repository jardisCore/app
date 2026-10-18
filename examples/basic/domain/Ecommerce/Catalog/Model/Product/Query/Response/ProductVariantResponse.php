<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Query\Response;

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

    /** @var numeric-string */
    public string $priceModifier = '0';
    public int $stockQuantity = 0;
    public ?int $lowStockThreshold = null;
    public ?int $weightGrams = null;
    public int $isAvailable = 0;
    public int $sortOrder = 0;
    public ?string $createdAt = null;

    /** @var ProductVariantPriceResponse[] */
    public array $productVariantPrice = [];
}

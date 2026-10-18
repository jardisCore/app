<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Query\Response;

use JardisSupport\Contract\Workflow\AggregateResponse;

/**
 * Read projection for ProductResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class ProductResponse implements AggregateResponse
{
    public string $identifier = '';
    public string $sku = '';
    public string $productName = '';
    public string $slug = '';
    public ?string $description = null;
    public ?string $shortDescription = null;

    /** @var numeric-string */
    public string $price = '0';

    /** @var ?numeric-string */
    public ?string $compareAtPrice = null;

    /** @var ?numeric-string */
    public ?string $costPrice = null;
    public string $currency = '';
    public ?int $weightGrams = null;
    public int $isActive = 0;
    public int $isFeatured = 0;
    public string $taxClass = '';
    public ?string $createdAt = null;
    public ?string $updatedAt = null;

    /** @var ProductVariantResponse[] */
    public array $productVariant = [];

    /** @var ProductImageResponse[] */
    public array $productImage = [];
}

<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Query\Response;

use DateTime;
use JardisSupport\Contract\Workflow\AggregateResponse;

/**
 * Read projection for ProductResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class ProductResponse implements AggregateResponse
{
    public int $id = 0;
    public string $identifier = '';
    public string $sku = '';
    public string $productName = '';
    public string $slug = '';
    public ?string $description = null;
    public ?string $shortDescription = null;
    public float $price = 0.0;
    public ?float $compareAtPrice = null;
    public ?float $costPrice = null;
    public string $currency = '';
    public ?int $weightGrams = null;
    public int $isActive = 0;
    public int $isFeatured = 0;
    public string $taxClass = '';
    public ?DateTime $createdAt = null;
    public ?DateTime $updatedAt = null;

    /** @var ProductVariantResponse[] */
    public array $productVariant = [];

    /** @var ProductImageResponse[] */
    public array $productImage = [];
}

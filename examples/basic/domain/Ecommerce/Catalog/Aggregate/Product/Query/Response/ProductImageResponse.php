<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Query\Response;

/**
 * Read projection for ProductImageResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class ProductImageResponse
{
    public int $id = 0;
    public string $url = '';
    public ?string $altText = null;
    public string $mimeType = '';
    public ?int $fileSize = null;
    public ?int $width = null;
    public ?int $height = null;
    public int $sortOrder = 0;
    public int $isPrimary = 0;
}

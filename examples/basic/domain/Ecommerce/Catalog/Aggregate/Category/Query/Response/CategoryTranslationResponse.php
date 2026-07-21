<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Query\Response;

/**
 * Read projection for CategoryTranslationResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class CategoryTranslationResponse
{
    public int $id = 0;
    public string $locale = '';
    public string $title = '';
    public ?string $description = null;
    public ?string $metaTitle = null;
    public ?string $metaDescription = null;
}

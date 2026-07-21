<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Query\Response;

use DateTime;
use JardisSupport\Contract\Workflow\AggregateResponse;

/**
 * Read projection for CategoryResponse.
 *
 * Typed, nested read shape (stripped outer form). Hydrated from the query
 * result via JardisSupport\Data\Hydration::hydrateAggregate().
 */
class CategoryResponse implements AggregateResponse
{
    public int $id = 0;
    public string $identifier = '';
    public string $slug = '';
    public ?string $parentIdentifier = null;
    public string $categoryName = '';
    public ?string $description = null;
    public ?string $metaTitle = null;
    public ?string $metaDescription = null;
    public int $isActive = 0;
    public int $isVisible = 0;
    public int $sortOrder = 0;
    public int $productCount = 0;
    public ?DateTime $createdAt = null;
    public ?DateTime $updatedAt = null;

    /** @var CategoryTranslationResponse[] */
    public array $categoryTranslation = [];
}

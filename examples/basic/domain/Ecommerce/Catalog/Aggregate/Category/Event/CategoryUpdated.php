<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Event;

use DateTimeImmutable;

/**
 * Event: Category was updated.
 *
 * Dispatched after Category fields are successfully persisted.
 */
readonly class CategoryUpdated
{
    /**
     * @param string $categoryIdentifier
     * @param string $slug
     * @param ?string $parentIdentifier
     * @param string $categoryName
     * @param ?string $description
     * @param ?string $metaTitle
     * @param ?string $metaDescription
     * @param int $isActive
     * @param int $isVisible
     * @param int $sortOrder
     * @param int $productCount
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $categoryIdentifier,
        public string $slug,
        public ?string $parentIdentifier,
        public string $categoryName,
        public ?string $description,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public int $isActive,
        public int $isVisible,
        public int $sortOrder,
        public int $productCount,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

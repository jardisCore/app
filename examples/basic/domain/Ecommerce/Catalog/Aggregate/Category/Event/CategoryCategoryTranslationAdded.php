<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Event;

use DateTimeImmutable;

/**
 * Event: CategoryTranslation was added.
 *
 * Dispatched after a new CategoryTranslation is added to the aggregate.
 */
readonly class CategoryCategoryTranslationAdded
{
    /**
     * @param string $categoryIdentifier
     * @param int $categoryTranslationId
     * @param string $locale
     * @param string $title
     * @param ?string $description
     * @param ?string $metaTitle
     * @param ?string $metaDescription
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $categoryIdentifier,
        public int $categoryTranslationId,
        public string $locale,
        public string $title,
        public ?string $description,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

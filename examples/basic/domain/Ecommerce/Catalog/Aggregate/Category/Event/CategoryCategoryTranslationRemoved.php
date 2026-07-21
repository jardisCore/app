<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Event;

use DateTimeImmutable;

/**
 * Event: CategoryTranslation was removed.
 *
 * Dispatched after a CategoryTranslation is removed from the aggregate.
 */
readonly class CategoryCategoryTranslationRemoved
{
    /**
     * @param string $categoryIdentifier
     * @param int $categoryTranslationId
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $categoryIdentifier,
        public int $categoryTranslationId,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

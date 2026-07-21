<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Event;

use DateTimeImmutable;

/**
 * Event: Category was removed.
 *
 * Dispatched after an aggregate is successfully deleted.
 */
readonly class CategoryRemoved
{
    /**
     * @param string $categoryIdentifier
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $categoryIdentifier,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

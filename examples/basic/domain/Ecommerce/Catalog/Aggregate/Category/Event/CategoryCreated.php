<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Event;

use DateTimeImmutable;

/**
 * Event: Category was created.
 *
 * Dispatched after a new aggregate is successfully persisted.
 */
readonly class CategoryCreated
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

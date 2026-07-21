<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Event;

use DateTimeImmutable;

/**
 * Event: Product was created.
 *
 * Dispatched after a new aggregate is successfully persisted.
 */
readonly class ProductCreated
{
    /**
     * @param string $productIdentifier
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $productIdentifier,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

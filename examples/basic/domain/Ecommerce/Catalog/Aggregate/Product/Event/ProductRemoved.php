<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Event;

use DateTimeImmutable;

/**
 * Event: Product was removed.
 *
 * Dispatched after an aggregate is successfully deleted.
 */
readonly class ProductRemoved
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

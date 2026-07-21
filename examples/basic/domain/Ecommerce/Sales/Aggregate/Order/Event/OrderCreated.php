<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Event;

use DateTimeImmutable;

/**
 * Event: Order was created.
 *
 * Dispatched after a new aggregate is successfully persisted.
 */
readonly class OrderCreated
{
    /**
     * @param string $orderNumber
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $orderNumber,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Event;

use DateTimeImmutable;

/**
 * Event: Order was removed.
 *
 * Dispatched after an aggregate is successfully deleted.
 */
readonly class OrderRemoved
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

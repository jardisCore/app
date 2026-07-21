<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Event;

use DateTimeImmutable;

/**
 * Event: OrderItem was removed.
 *
 * Dispatched after a OrderItem is removed from the aggregate.
 */
readonly class OrderOrderItemRemoved
{
    /**
     * @param string $orderNumber
     * @param string $orderItemIdentifier
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $orderNumber,
        public string $orderItemIdentifier,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

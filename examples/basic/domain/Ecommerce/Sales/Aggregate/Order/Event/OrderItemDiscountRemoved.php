<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Event;

use DateTimeImmutable;

/**
 * Event: ItemDiscount was removed.
 *
 * Dispatched after a ItemDiscount is removed from the aggregate.
 */
readonly class OrderItemDiscountRemoved
{
    /**
     * @param string $orderNumber
     * @param int $itemDiscountId
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $orderNumber,
        public int $itemDiscountId,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Event;

use DateTimeImmutable;

/**
 * Event: ItemDiscount was added.
 *
 * Dispatched after a new ItemDiscount is added to the aggregate.
 */
readonly class OrderItemDiscountAdded
{
    /**
     * @param string $orderNumber
     * @param int $itemDiscountId
     * @param string $discountCode
     * @param float $amount
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $orderNumber,
        public int $itemDiscountId,
        public string $discountCode,
        public float $amount,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

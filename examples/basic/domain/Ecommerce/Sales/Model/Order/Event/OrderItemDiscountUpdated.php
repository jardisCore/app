<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Event;

use DateTimeImmutable;

/**
 * Event: ItemDiscount was updated.
 *
 * Dispatched after ItemDiscount fields are successfully persisted.
 */
readonly class OrderItemDiscountUpdated
{
    /**
     * @param string $orderNumber
     * @param int $itemDiscountId
     * @param string $discountCode
     * @param numeric-string $amount
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $orderNumber,
        public int $itemDiscountId,
        public string $discountCode,
        public string $amount,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

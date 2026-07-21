<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Event;

use DateTimeImmutable;

/**
 * Event: OrderItem was added.
 *
 * Dispatched after a new OrderItem is added to the aggregate.
 */
readonly class OrderOrderItemAdded
{
    /**
     * @param string $orderNumber
     * @param string $orderItemIdentifier
     * @param string $productIdentifier
     * @param int $quantity
     * @param float $unitPrice
     * @param float $subtotal
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $orderNumber,
        public string $orderItemIdentifier,
        public string $productIdentifier,
        public int $quantity,
        public float $unitPrice,
        public float $subtotal,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

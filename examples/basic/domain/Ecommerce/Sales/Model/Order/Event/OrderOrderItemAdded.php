<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Event;

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
     * @param string $tenantKey
     * @param string $productIdentifier
     * @param int $quantity
     * @param string $unitPrice
     * @param string $subtotal
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $orderNumber,
        public string $orderItemIdentifier,
        public string $tenantKey,
        public string $productIdentifier,
        public int $quantity,
        public string $unitPrice,
        public string $subtotal,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

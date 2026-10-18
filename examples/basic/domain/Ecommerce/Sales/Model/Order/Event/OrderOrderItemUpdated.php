<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Event;

use DateTimeImmutable;

/**
 * Event: OrderItem was updated.
 *
 * Dispatched after OrderItem fields are successfully persisted.
 */
readonly class OrderOrderItemUpdated
{
    /**
     * @param string $orderNumber
     * @param string $orderItemIdentifier
     * @param string $productIdentifier
     * @param int $quantity
     * @param numeric-string $unitPrice
     * @param numeric-string $subtotal
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $orderNumber,
        public string $orderItemIdentifier,
        public string $productIdentifier,
        public int $quantity,
        public string $unitPrice,
        public string $subtotal,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

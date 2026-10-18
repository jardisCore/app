<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Event;

use DateTimeImmutable;

/**
 * Event: Order was updated.
 *
 * Dispatched after Order fields are successfully persisted.
 */
readonly class OrderUpdated
{
    /**
     * @param string $orderNumber
     * @param string $totalAmount
     * @param string $status
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $orderNumber,
        public string $totalAmount,
        public string $status,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

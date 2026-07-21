<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Event;

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
     * @param float $totalAmount
     * @param string $status
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $orderNumber,
        public float $totalAmount,
        public string $status,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

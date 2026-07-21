<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Event;

use DateTimeImmutable;

/**
 * Event: Address was removed.
 *
 * Dispatched after a Address is removed from the aggregate.
 */
readonly class OrderAddressRemoved
{
    /**
     * @param string $orderNumber
     * @param int $addressId
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $orderNumber,
        public int $addressId,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

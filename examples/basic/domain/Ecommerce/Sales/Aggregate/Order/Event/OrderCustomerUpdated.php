<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Event;

use DateTimeImmutable;

/**
 * Event: Customer was updated.
 *
 * Dispatched after Customer fields are successfully persisted.
 */
readonly class OrderCustomerUpdated
{
    /**
     * @param string $orderNumber
     * @param string $email
     * @param string $customerName
     * @param ?string $phone
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $orderNumber,
        public string $email,
        public string $customerName,
        public ?string $phone,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

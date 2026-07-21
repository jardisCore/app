<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Event;

use DateTimeImmutable;

/**
 * Event: Address was updated.
 *
 * Dispatched after Address fields are successfully persisted.
 */
readonly class OrderAddressUpdated
{
    /**
     * @param string $orderNumber
     * @param string $street
     * @param string $city
     * @param string $postalCode
     * @param string $country
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $orderNumber,
        public string $street,
        public string $city,
        public string $postalCode,
        public string $country,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

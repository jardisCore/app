<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Event;

use DateTimeImmutable;

/**
 * Event: PriceTier was removed.
 *
 * Dispatched after an aggregate is successfully deleted.
 */
readonly class PriceTierRemoved
{
    /**
     * @param string $priceTierIdentifier
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $priceTierIdentifier,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

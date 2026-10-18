<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Event;

use DateTimeImmutable;

/**
 * Event: PriceTier was created.
 *
 * Dispatched after a new aggregate is successfully persisted.
 */
readonly class PriceTierCreated
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

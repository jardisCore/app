<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Event;

use DateTimeImmutable;

/**
 * Event: PriceTier was updated.
 *
 * Dispatched after PriceTier fields are successfully persisted.
 */
readonly class PriceTierUpdated
{
    /**
     * @param string $priceTierIdentifier
     * @param string $tierCode
     * @param int $minQuantity
     * @param string $unitPrice
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $priceTierIdentifier,
        public string $tierCode,
        public int $minQuantity,
        public string $unitPrice,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

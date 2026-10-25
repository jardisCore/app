<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Command;

/**
 * Command data class for PriceTier.
 *
 * Readonly DTO for command operations.
 */
readonly class PriceTier
{
    public function __construct(
        public string $tenant,
        public string $tierCode,
        public int $minQuantity,
        public string $unitPrice
    ) {
    }
}

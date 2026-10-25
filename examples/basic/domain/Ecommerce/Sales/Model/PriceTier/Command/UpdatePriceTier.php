<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Command;

/**
 * Command data class for UpdatePriceTier.
 *
 * Readonly DTO for command operations.
 */
readonly class UpdatePriceTier
{
    public function __construct(
        public string $tenant,
        public string $priceTierIdentifier,
        public string $tierCode,
        public int $minQuantity,
        public string $unitPrice
    ) {
    }
}

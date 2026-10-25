<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Command;

/**
 * Command data class for RemovePriceTier.
 *
 * Readonly DTO for command operations.
 */
readonly class RemovePriceTier
{
    public function __construct(
        public string $tenant,
        public string $priceTierIdentifier
    ) {
    }
}

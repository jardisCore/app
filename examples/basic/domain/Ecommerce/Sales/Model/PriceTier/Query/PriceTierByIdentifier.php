<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Query;

/**
 * Query data class for PriceTierByIdentifier.
 *
 * Readonly DTO for query operations.
 */
readonly class PriceTierByIdentifier
{
    public function __construct(
        public string $tenant,
        public string $identifier
    ) {
    }
}

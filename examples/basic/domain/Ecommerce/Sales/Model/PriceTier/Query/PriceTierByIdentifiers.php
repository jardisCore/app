<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Query;

/**
 * Query data class for PriceTierByIdentifiers.
 *
 * Readonly DTO for query operations.
 */
readonly class PriceTierByIdentifiers
{
    public function __construct(
        public array $identifiers
    ) {
    }
}

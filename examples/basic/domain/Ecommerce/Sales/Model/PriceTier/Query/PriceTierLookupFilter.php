<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Query;

/**
 * Query data class for PriceTierLookupFilter.
 *
 * Readonly DTO for query operations.
 */
readonly class PriceTierLookupFilter
{
    public function __construct(
        public string $tierCode,
        public int $limit = 20,
        public int $offset = 0
    ) {
    }
}

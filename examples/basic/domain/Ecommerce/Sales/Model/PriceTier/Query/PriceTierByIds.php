<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Query;

/**
 * Query data class for PriceTierByIds.
 *
 * Readonly DTO for query operations.
 */
readonly class PriceTierByIds
{
    public function __construct(
        public array $ids
    ) {
    }
}

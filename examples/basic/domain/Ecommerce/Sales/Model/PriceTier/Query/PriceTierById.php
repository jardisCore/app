<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Query;

/**
 * Query data class for PriceTierById.
 *
 * Readonly DTO for query operations.
 */
readonly class PriceTierById
{
    public function __construct(
        public int $id
    ) {
    }
}

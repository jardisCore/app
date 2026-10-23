<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Query;

/**
 * Query data class for InvoicesOfActorTenantFilter.
 *
 * Readonly DTO for query operations.
 */
readonly class InvoicesOfActorTenantFilter
{
    public function __construct(
        public int $limit = 20,
        public int $offset = 0
    ) {
    }
}

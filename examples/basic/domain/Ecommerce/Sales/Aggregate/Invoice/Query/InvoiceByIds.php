<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Query;

/**
 * Query data class for InvoiceByIds.
 *
 * Readonly DTO for query operations.
 */
readonly class InvoiceByIds
{
    public function __construct(
        public array $ids
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Query;

/**
 * Query data class for InvoiceById.
 *
 * Readonly DTO for query operations.
 */
readonly class InvoiceById
{
    public function __construct(
        public int $id
    ) {
    }
}

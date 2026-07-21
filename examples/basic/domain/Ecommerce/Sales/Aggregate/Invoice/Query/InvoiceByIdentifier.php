<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Query;

/**
 * Query data class for InvoiceByIdentifier.
 *
 * Readonly DTO for query operations.
 */
readonly class InvoiceByIdentifier
{
    public function __construct(
        public string $identifier
    ) {
    }
}

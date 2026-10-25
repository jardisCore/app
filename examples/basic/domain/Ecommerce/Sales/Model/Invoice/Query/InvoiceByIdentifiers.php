<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Query;

/**
 * Query data class for InvoiceByIdentifiers.
 *
 * Readonly DTO for query operations.
 */
readonly class InvoiceByIdentifiers
{
    public function __construct(
        public string $tenant,
        public array $identifiers
    ) {
    }
}

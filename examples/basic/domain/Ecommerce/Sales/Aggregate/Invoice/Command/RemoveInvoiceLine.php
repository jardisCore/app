<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Command;

/**
 * Command data class for RemoveInvoiceLine.
 *
 * Readonly DTO for command operations.
 */
readonly class RemoveInvoiceLine
{
    public function __construct(
        public string $invoiceIdentifier,
        public string $invoiceLineIdentifier
    ) {
    }
}

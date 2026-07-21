<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Command;

/**
 * Command data class for RemoveInvoice.
 *
 * Readonly DTO for command operations.
 */
readonly class RemoveInvoice
{
    public function __construct(
        public string $invoiceIdentifier
    ) {
    }
}

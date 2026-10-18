<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Command;

/**
 * Command data class for UpdateInvoiceLine.
 *
 * Readonly DTO for command operations.
 */
readonly class UpdateInvoiceLine
{
    public function __construct(
        public string $invoiceIdentifier,
        public string $invoiceLineIdentifier,
        public int $position,
        public string $description,
        public string $quantity,
        public string $unit,
        public string $unitPrice,
        public ?string $discountPercent,
        public string $lineTotal,
        public int $taxIncluded
    ) {
    }
}

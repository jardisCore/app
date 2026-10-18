<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Command;

/**
 * Command data class for InvoiceLine.
 *
 * Readonly DTO for command operations.
 */
readonly class InvoiceLine
{
    public function __construct(
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

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Command;

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
        public float $quantity,
        public string $unit,
        public float $unitPrice,
        public ?float $discountPercent,
        public float $lineTotal,
        public int $taxIncluded
    ) {
    }
}

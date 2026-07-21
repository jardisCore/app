<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Event;

use DateTimeImmutable;

/**
 * Event: InvoiceLine was added.
 *
 * Dispatched after a new InvoiceLine is added to the aggregate.
 */
readonly class InvoiceInvoiceLineAdded
{
    /**
     * @param string $invoiceIdentifier
     * @param string $invoiceLineIdentifier
     * @param int $position
     * @param string $description
     * @param float $quantity
     * @param string $unit
     * @param float $unitPrice
     * @param ?float $discountPercent
     * @param float $lineTotal
     * @param int $taxIncluded
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $invoiceIdentifier,
        public string $invoiceLineIdentifier,
        public int $position,
        public string $description,
        public float $quantity,
        public string $unit,
        public float $unitPrice,
        public ?float $discountPercent,
        public float $lineTotal,
        public int $taxIncluded,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Event;

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
     * @param string $quantity
     * @param string $unit
     * @param string $unitPrice
     * @param ?string $discountPercent
     * @param string $lineTotal
     * @param int $taxIncluded
     * @param DateTimeImmutable $occurredAt
     */
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
        public int $taxIncluded,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

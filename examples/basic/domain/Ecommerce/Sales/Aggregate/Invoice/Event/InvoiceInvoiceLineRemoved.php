<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Event;

use DateTimeImmutable;

/**
 * Event: InvoiceLine was removed.
 *
 * Dispatched after a InvoiceLine is removed from the aggregate.
 */
readonly class InvoiceInvoiceLineRemoved
{
    /**
     * @param string $invoiceIdentifier
     * @param string $invoiceLineIdentifier
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $invoiceIdentifier,
        public string $invoiceLineIdentifier,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

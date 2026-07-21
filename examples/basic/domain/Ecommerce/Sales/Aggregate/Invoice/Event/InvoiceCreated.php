<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Event;

use DateTimeImmutable;

/**
 * Event: Invoice was created.
 *
 * Dispatched after a new aggregate is successfully persisted.
 */
readonly class InvoiceCreated
{
    /**
     * @param string $invoiceIdentifier
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $invoiceIdentifier,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

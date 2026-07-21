<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Event;

use DateTimeImmutable;

/**
 * Event: Invoice was removed.
 *
 * Dispatched after an aggregate is successfully deleted.
 */
readonly class InvoiceRemoved
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

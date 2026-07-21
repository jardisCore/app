<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Query;

/**
 * Filter DTO for InvoiceLineList list query.
 *
 * Generated code - do not modify directly.
 */
readonly class InvoiceLineListFilter
{
    public function __construct(
        public string $invoiceNumber,
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}

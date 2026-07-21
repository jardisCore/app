<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Query;

/**
 * Filter DTO for InvoiceList list query.
 *
 * Generated code - do not modify directly.
 */
readonly class InvoiceListFilter
{
    public function __construct(
        public ?string $status = null,
        public ?string $customerIdentifier = null,
        public int $limit = 20,
        public int $offset = 0,
    ) {
    }
}

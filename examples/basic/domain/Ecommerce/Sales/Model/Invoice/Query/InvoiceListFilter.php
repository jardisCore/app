<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Query;

/**
 * Query data class for InvoiceListFilter.
 *
 * Readonly DTO for query operations.
 */
readonly class InvoiceListFilter
{
    public function __construct(
        public ?int $id = null,
        public ?string $identifier = null,
        public ?string $invoiceNumber = null,
        public ?string $orderNumber = null,
        public ?string $customerIdentifier = null,
        public ?string $status = null,
        public ?string $paymentMethod = null,
        public ?float $totalNet = null,
        public ?float $taxRate = null,
        public ?float $totalGross = null,
        public ?string $currency = null,
        public ?string $note = null,
        public ?string $issuedAtFrom = null,
        public ?string $issuedAtUntil = null,
        public ?string $dueAtFrom = null,
        public ?string $dueAtUntil = null,
        public ?string $paidAtFrom = null,
        public ?string $paidAtUntil = null,
        public ?string $createdAtFrom = null,
        public ?string $createdAtUntil = null,
        public int $limit = 20,
        public int $offset = 0
    ) {
    }
}

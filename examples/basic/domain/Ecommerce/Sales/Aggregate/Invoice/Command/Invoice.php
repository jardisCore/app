<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Command;

use DateTimeImmutable;

/**
 * Command data class for Invoice.
 *
 * Readonly DTO for command operations.
 */
readonly class Invoice
{
    public function __construct(
        public string $invoiceNumber,
        public string $orderNumber,
        public string $customerIdentifier,
        public string $status,
        public ?string $paymentMethod,
        public float $totalNet,
        public float $taxRate,
        public float $totalGross,
        public string $currency,
        public ?string $note,
        public ?DateTimeImmutable $issuedAt,
        public ?DateTimeImmutable $dueAt,
        public ?DateTimeImmutable $paidAt,
        /** @var array<InvoiceLine> */
        public array $invoiceLine
    ) {
    }
}

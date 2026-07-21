<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Event;

use DateTimeImmutable;

/**
 * Event: Invoice was updated.
 *
 * Dispatched after Invoice fields are successfully persisted.
 */
readonly class InvoiceUpdated
{
    /**
     * @param string $invoiceIdentifier
     * @param string $invoiceNumber
     * @param string $orderNumber
     * @param string $customerIdentifier
     * @param string $status
     * @param ?string $paymentMethod
     * @param float $totalNet
     * @param float $taxRate
     * @param float $totalGross
     * @param string $currency
     * @param ?string $note
     * @param ?DateTimeImmutable $issuedAt
     * @param ?DateTimeImmutable $dueAt
     * @param ?DateTimeImmutable $paidAt
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $invoiceIdentifier,
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
        public DateTimeImmutable $occurredAt
    ) {
    }
}

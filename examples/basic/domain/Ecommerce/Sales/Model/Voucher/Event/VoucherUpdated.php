<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Event;

use DateTimeImmutable;

/**
 * Event: Voucher was updated.
 *
 * Dispatched after Voucher fields are successfully persisted.
 */
readonly class VoucherUpdated
{
    /**
     * @param string $voucherCode
     * @param string $scopeKey
     * @param string $amount
     * @param ?string $note
     * @param string $issuedBy
     * @param string $issuerId
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $voucherCode,
        public string $scopeKey,
        public string $amount,
        public ?string $note,
        public string $issuedBy,
        public string $issuerId,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Event;

use DateTimeImmutable;

/**
 * Event: Voucher was created.
 *
 * Dispatched after a new aggregate is successfully persisted.
 */
readonly class VoucherCreated
{
    /**
     * @param string $voucherCode
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $voucherCode,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Event;

use DateTimeImmutable;

/**
 * Event: Voucher was removed.
 *
 * Dispatched after an aggregate is successfully deleted.
 */
readonly class VoucherRemoved
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

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Event;

use DateTimeImmutable;

/**
 * Event: VoucherChannel was updated.
 *
 * Dispatched after VoucherChannel fields are successfully persisted.
 */
readonly class VoucherVoucherChannelUpdated
{
    /**
     * @param string $voucherCode
     * @param string $label
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $voucherCode,
        public string $label,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

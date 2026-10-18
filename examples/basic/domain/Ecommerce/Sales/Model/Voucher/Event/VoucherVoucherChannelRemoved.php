<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Event;

use DateTimeImmutable;

/**
 * Event: VoucherChannel was removed.
 *
 * Dispatched after a VoucherChannel is removed from the aggregate.
 */
readonly class VoucherVoucherChannelRemoved
{
    /**
     * @param string $voucherCode
     * @param string $voucherChannelIdentifier
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $voucherCode,
        public string $voucherChannelIdentifier,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

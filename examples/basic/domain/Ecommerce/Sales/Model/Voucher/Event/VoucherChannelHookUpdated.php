<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Event;

use DateTimeImmutable;

/**
 * Event: ChannelHook was updated.
 *
 * Dispatched after ChannelHook fields are successfully persisted.
 */
readonly class VoucherChannelHookUpdated
{
    /**
     * @param string $voucherCode
     * @param string $targetUrl
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $voucherCode,
        public string $targetUrl,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

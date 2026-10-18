<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Event;

use DateTimeImmutable;

/**
 * Event: ChannelHook was removed.
 *
 * Dispatched after a ChannelHook is removed from the aggregate.
 */
readonly class VoucherChannelHookRemoved
{
    /**
     * @param string $voucherCode
     * @param int $channelHookId
     * @param DateTimeImmutable $occurredAt
     */
    public function __construct(
        public string $voucherCode,
        public int $channelHookId,
        public DateTimeImmutable $occurredAt
    ) {
    }
}

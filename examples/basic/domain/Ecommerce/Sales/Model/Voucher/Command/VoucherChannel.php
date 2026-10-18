<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command;

/**
 * Command data class for VoucherChannel.
 *
 * Readonly DTO for command operations.
 */
readonly class VoucherChannel
{
    public function __construct(
        public string $label,
        public string $accessKey,
        public ChannelHook $channelHook
    ) {
    }
}

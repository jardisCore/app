<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command;

/**
 * Command data class for SetVoucherChannel.
 *
 * Readonly DTO for command operations.
 */
readonly class SetVoucherChannel
{
    public function __construct(
        public string $scopeKey,
        public string $voucherCode,
        public string $label,
        public ?string $accessKey,
        public ChannelHookOfSetVoucherChannel $channelHook
    ) {
    }
}

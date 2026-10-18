<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command;

/**
 * Command data class for ChannelHookOfSetVoucherChannel.
 *
 * Readonly DTO for command operations.
 */
readonly class ChannelHookOfSetVoucherChannel
{
    public function __construct(
        public string $targetUrl,
        public ?string $signingKey
    ) {
    }
}

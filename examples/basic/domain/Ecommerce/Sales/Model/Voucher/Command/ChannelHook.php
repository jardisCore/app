<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command;

/**
 * Command data class for ChannelHook.
 *
 * Readonly DTO for command operations.
 */
readonly class ChannelHook
{
    public function __construct(
        public string $targetUrl,
        public string $signingKey
    ) {
    }
}

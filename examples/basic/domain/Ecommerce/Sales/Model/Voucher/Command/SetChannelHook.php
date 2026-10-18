<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command;

/**
 * Command data class for SetChannelHook.
 *
 * Readonly DTO for command operations.
 */
readonly class SetChannelHook
{
    public function __construct(
        public string $scopeKey,
        public string $voucherCode,
        public string $targetUrl,
        public ?string $signingKey
    ) {
    }
}

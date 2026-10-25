<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command;

/**
 * Command data class for RemoveChannelHook.
 *
 * Readonly DTO for command operations.
 */
readonly class RemoveChannelHook
{
    public function __construct(
        public string $tenant,
        public string $voucherCode
    ) {
    }
}

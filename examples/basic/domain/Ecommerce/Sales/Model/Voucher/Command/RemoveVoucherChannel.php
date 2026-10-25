<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command;

/**
 * Command data class for RemoveVoucherChannel.
 *
 * Readonly DTO for command operations.
 */
readonly class RemoveVoucherChannel
{
    public function __construct(
        public string $tenant,
        public string $voucherCode
    ) {
    }
}

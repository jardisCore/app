<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\VoucherRedeemCheck\Command;

/**
 * Process DTO for VoucherRedeemCheck.
 *
 * Readonly DTO for command/query operations.
 */
readonly class VoucherRedeemCheck
{
    public function __construct(
        public string $tenant,
        public string $user,
        public string $voucherCode
    ) {
    }
}

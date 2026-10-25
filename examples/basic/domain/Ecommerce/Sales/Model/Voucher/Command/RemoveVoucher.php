<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command;

/**
 * Command data class for RemoveVoucher.
 *
 * Readonly DTO for command operations.
 */
readonly class RemoveVoucher
{
    public function __construct(
        public string $tenant,
        public string $voucherCode
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Query;

/**
 * Query data class for VoucherByVoucherCode.
 *
 * Readonly DTO for query operations.
 */
readonly class VoucherByVoucherCode
{
    public function __construct(
        public string $tenant,
        public string $voucherCode
    ) {
    }
}

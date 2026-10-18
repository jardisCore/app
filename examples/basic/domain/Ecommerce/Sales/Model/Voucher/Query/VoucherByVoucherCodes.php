<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Query;

/**
 * Query data class for VoucherByVoucherCodes.
 *
 * Readonly DTO for query operations.
 */
readonly class VoucherByVoucherCodes
{
    public function __construct(
        public string $scopeKey,
        public array $voucherCodes
    ) {
    }
}

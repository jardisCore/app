<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Query;

/**
 * Query data class for VoucherByIds.
 *
 * Readonly DTO for query operations.
 */
readonly class VoucherByIds
{
    public function __construct(
        public array $ids
    ) {
    }
}

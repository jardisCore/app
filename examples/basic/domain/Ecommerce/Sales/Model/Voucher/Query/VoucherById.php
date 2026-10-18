<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Query;

/**
 * Query data class for VoucherById.
 *
 * Readonly DTO for query operations.
 */
readonly class VoucherById
{
    public function __construct(
        public int $id
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Query;

/**
 * Query data class for VoucherDigestListFilter.
 *
 * Readonly DTO for query operations.
 */
readonly class VoucherDigestListFilter
{
    public function __construct(
        public string $scopeKey,
        public string $voucherCode,
        public int $limit = 20,
        public int $offset = 0
    ) {
    }
}

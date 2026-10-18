<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Query;

/**
 * Query data class for VoucherChannelKeyListFilter.
 *
 * Readonly DTO for query operations.
 */
readonly class VoucherChannelKeyListFilter
{
    public function __construct(
        public string $scopeKey,
        public string $label,
        public int $limit = 20,
        public int $offset = 0
    ) {
    }
}

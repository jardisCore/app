<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Query;

/**
 * Query data class for VoucherListFilter.
 *
 * Readonly DTO for query operations.
 */
readonly class VoucherListFilter
{
    public function __construct(
        public string $scopeKey,
        public ?string $voucherCode = null,
        public ?string $note = null,
        public int $limit = 20,
        public int $offset = 0
    ) {
    }
}

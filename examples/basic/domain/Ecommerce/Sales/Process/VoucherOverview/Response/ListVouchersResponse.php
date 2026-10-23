<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\VoucherOverview\Response;

/**
 * Typed answer of the node ListVouchers of the process VoucherOverview.
 *
 * Hermetic - the fields are the declared response of the node.
 * The field vouchers carries the rows of the query voucherDigestList; the door cuts every row to its outer form before
 * the answer leaves.
 */
final readonly class ListVouchersResponse
{
    public function __construct(
        public array $vouchers,
    ) {
    }
}

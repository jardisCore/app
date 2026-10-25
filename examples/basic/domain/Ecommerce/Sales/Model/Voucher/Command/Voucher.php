<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command;

/**
 * Command data class for Voucher.
 *
 * Readonly DTO for command operations.
 */
readonly class Voucher
{
    public function __construct(
        public string $tenant,
        public string $voucherCode,
        public string $amount,
        public ?string $note,
        public string $secretDigest,
        public string $user,
        public VoucherChannel $voucherChannel
    ) {
    }
}

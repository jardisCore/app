<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command;

/**
 * Command data class for UpdateVoucher.
 *
 * Readonly DTO for command operations.
 */
readonly class UpdateVoucher
{
    public function __construct(
        public string $tenant,
        public string $voucherCode,
        public string $amount,
        public ?string $note,
        public ?string $secretDigest,
        public string $user
    ) {
    }
}

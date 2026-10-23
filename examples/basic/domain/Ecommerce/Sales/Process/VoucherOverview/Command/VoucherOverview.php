<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\VoucherOverview\Command;

/**
 * Process DTO for VoucherOverview.
 *
 * Readonly DTO for command/query operations.
 */
readonly class VoucherOverview
{
    public function __construct(
        public string $scope
    ) {
    }
}

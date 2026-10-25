<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderLimitCheck\Command;

/**
 * Process DTO for OrderLimitCheck.
 *
 * Readonly DTO for command/query operations.
 */
readonly class OrderLimitCheck
{
    public function __construct(
        public string $limit,
        public string $orderNumberToCheck,
        public string $tenant,
        public string $user
    ) {
    }
}

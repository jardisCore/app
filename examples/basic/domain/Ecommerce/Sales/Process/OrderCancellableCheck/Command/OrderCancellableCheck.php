<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderCancellableCheck\Command;

/**
 * Process DTO for OrderCancellableCheck.
 *
 * Readonly DTO for command/query operations.
 */
readonly class OrderCancellableCheck
{
    public function __construct(
        public string $orderNumberToCheck
    ) {
    }
}

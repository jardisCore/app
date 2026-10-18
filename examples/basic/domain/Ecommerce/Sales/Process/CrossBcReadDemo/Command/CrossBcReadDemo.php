<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\CrossBcReadDemo\Command;

/**
 * Process DTO for CrossBcReadDemo.
 *
 * Readonly DTO for command/query operations.
 */
readonly class CrossBcReadDemo
{
    public function __construct(
        public string $productIdentifier
    ) {
    }
}

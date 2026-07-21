<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\CrossBcServiceDemo\Command;

/**
 * Process DTO for CrossBcServiceDemo.
 *
 * Readonly DTO for command/query operations.
 */
readonly class CrossBcServiceDemo
{
    public function __construct(
        public string $clientIdentifier,
        public string $counterActiveFrom,
        public string $counterIdentifier,
        public string $counterNumber,
        public string $meterLocationIdentifier,
        public float $newPrice,
        public string $productIdentifier
    ) {
    }
}

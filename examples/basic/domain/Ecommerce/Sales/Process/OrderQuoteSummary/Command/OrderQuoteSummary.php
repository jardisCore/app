<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderQuoteSummary\Command;

/**
 * Process DTO for OrderQuoteSummary.
 *
 * Readonly DTO for command/query operations.
 */
readonly class OrderQuoteSummary
{
    public function __construct(
        public string $quoteNumber
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderQuoteSummary\Response;

/**
 * Typed answer of the node TotalQuote of the process OrderQuoteSummary.
 *
 * Hermetic - the fields are the declared response of the node.
 */
final readonly class TotalQuoteResponse
{
    public function __construct(
        public int $lineCount,
        public string $total,
    ) {
    }
}

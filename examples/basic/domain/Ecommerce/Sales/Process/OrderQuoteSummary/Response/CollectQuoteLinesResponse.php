<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderQuoteSummary\Response;

/**
 * Typed answer of the node CollectQuoteLines of the process OrderQuoteSummary.
 *
 * Hermetic - the fields are the declared response of the node.
 */
final readonly class CollectQuoteLinesResponse
{
    public function __construct(
        public string $currency,
        public int $lineCount,
    ) {
    }
}

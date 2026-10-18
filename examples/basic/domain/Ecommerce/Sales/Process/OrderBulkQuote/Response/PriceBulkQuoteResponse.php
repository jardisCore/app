<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderBulkQuote\Response;

/**
 * Typed answer of the node PriceBulkQuote of the process OrderBulkQuote.
 *
 * Hermetic - the fields are the declared response of the node.
 */
final readonly class PriceBulkQuoteResponse
{
    public function __construct(
        public int $lineCount,
        public int $discountedLines,
        public string $city,
        public int $voucherCount,
    ) {
    }
}

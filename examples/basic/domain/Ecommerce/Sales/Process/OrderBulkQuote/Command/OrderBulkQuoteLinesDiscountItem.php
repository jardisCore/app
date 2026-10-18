<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderBulkQuote\Command;

/**
 * Input item OrderBulkQuoteLinesDiscountItem of process OrderBulkQuote.
 *
 * Readonly value of one object field of the process input.
 */
readonly class OrderBulkQuoteLinesDiscountItem
{
    public function __construct(
        public string $percent,
        public ?string $reason
    ) {
    }
}

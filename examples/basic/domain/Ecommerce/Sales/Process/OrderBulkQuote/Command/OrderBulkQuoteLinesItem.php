<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderBulkQuote\Command;

/**
 * Input item OrderBulkQuoteLinesItem of process OrderBulkQuote.
 *
 * Readonly value of one object field of the process input.
 */
readonly class OrderBulkQuoteLinesItem
{
    public function __construct(
        public ?OrderBulkQuoteLinesDiscountItem $discount,
        public int $quantity,
        /** @var list<string>|null */
        public ?array $serialNumbers,
        public string $sku,
        public string $unitPrice
    ) {
    }
}

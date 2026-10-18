<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderBulkQuote\Command;

/**
 * Process DTO for OrderBulkQuote.
 *
 * Readonly DTO for command/query operations.
 */
readonly class OrderBulkQuote
{
    public function __construct(
        /** @param list<string>|null $deliveryChannels */
        public ?array $deliveryChannels,
        /** @param list<OrderBulkQuoteLinesItem> $lines */
        public array $lines,
        public string $requestedBy,
        public OrderBulkQuoteShippingAddressItem $shippingAddress,
        /** @param list<string> $voucherCodes */
        public array $voucherCodes
    ) {
    }
}

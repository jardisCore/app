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
        /** @var list<string>|null */
        public ?array $deliveryChannels,
        /** @var list<OrderBulkQuoteLinesItem> */
        public array $lines,
        public string $requestedBy,
        public OrderBulkQuoteShippingAddressItem $shippingAddress,
        public string $tenant,
        public string $user,
        /** @var list<string> */
        public array $voucherCodes
    ) {
    }
}

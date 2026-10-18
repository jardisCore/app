<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderBulkQuote\Command;

/**
 * Input item OrderBulkQuoteShippingAddressItem of process OrderBulkQuote.
 *
 * Readonly value of one object field of the process input.
 */
readonly class OrderBulkQuoteShippingAddressItem
{
    public function __construct(
        public string $city,
        public ?string $postalCode,
        public string $street
    ) {
    }
}

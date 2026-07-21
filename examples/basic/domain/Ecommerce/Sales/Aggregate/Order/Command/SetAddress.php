<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command;

/**
 * Command data class for SetAddress.
 *
 * Readonly DTO for command operations.
 */
readonly class SetAddress
{
    public function __construct(
        public string $orderNumber,
        public string $street,
        public string $city,
        public string $postalCode,
        public string $country
    ) {
    }
}

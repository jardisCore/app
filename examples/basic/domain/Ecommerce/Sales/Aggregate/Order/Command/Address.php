<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command;

/**
 * Command data class for Address.
 *
 * Readonly DTO for command operations.
 */
readonly class Address
{
    public function __construct(
        public string $street,
        public string $city,
        public string $postalCode,
        public string $country
    ) {
    }
}

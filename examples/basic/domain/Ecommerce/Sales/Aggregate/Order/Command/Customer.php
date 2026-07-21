<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command;

/**
 * Command data class for Customer.
 *
 * Readonly DTO for command operations.
 */
readonly class Customer
{
    public function __construct(
        public string $email,
        public string $customerName,
        public ?string $phone,
        public Address $address
    ) {
    }
}

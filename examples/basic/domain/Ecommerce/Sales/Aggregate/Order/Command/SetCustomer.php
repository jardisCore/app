<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command;

/**
 * Command data class for SetCustomer.
 *
 * Readonly DTO for command operations.
 */
readonly class SetCustomer
{
    public function __construct(
        public string $orderNumber,
        public string $email,
        public string $customerName,
        public ?string $phone,
        public Address $address
    ) {
    }
}

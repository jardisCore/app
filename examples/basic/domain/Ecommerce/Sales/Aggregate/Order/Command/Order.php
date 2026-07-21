<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command;

/**
 * Command data class for Order.
 *
 * Readonly DTO for command operations.
 */
readonly class Order
{
    public function __construct(
        public string $orderNumber,
        public float $totalAmount,
        public string $status,
        public Customer $customer,
        /** @var array<OrderItem> */
        public array $orderItem
    ) {
    }
}

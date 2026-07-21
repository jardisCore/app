<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command;

/**
 * Command data class for UpdateOrder.
 *
 * Readonly DTO for command operations.
 */
readonly class UpdateOrder
{
    public function __construct(
        public string $orderNumber,
        public float $totalAmount,
        public string $status
    ) {
    }
}

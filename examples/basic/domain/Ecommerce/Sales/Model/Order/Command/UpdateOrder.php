<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command;

/**
 * Command data class for UpdateOrder.
 *
 * Readonly DTO for command operations.
 */
readonly class UpdateOrder
{
    public function __construct(
        public string $orderNumber,
        public string $totalAmount,
        public string $status
    ) {
    }
}

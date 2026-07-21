<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command;

/**
 * Command data class for RemoveOrder.
 *
 * Readonly DTO for command operations.
 */
readonly class RemoveOrder
{
    public function __construct(
        public string $orderNumber
    ) {
    }
}

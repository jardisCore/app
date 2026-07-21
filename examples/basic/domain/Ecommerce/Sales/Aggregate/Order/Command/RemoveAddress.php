<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command;

/**
 * Command data class for RemoveAddress.
 *
 * Readonly DTO for command operations.
 */
readonly class RemoveAddress
{
    public function __construct(
        public string $orderNumber
    ) {
    }
}

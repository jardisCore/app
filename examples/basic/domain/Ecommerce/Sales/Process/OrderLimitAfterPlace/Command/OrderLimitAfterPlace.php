<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderLimitAfterPlace\Command;

use Ecommerce\Sales\Model\Order\Command\Order;

/**
 * Process DTO for OrderLimitAfterPlace.
 *
 * Readonly DTO for command/query operations.
 */
readonly class OrderLimitAfterPlace
{
    public function __construct(
        public string $limit,
        public Order $order
    ) {
    }
}

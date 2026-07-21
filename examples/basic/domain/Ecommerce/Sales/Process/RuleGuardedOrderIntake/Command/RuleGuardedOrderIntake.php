<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command;

use Ecommerce\Sales\Aggregate\Order\Command\Order;

/**
 * Process DTO for RuleGuardedOrderIntake.
 *
 * Readonly DTO for command/query operations.
 */
readonly class RuleGuardedOrderIntake
{
    public function __construct(
        public Order $order
    ) {
    }
}

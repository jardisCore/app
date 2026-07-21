<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Rule\Guard;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Rule\Data\RuleResult;
use Ecommerce\Sales\Rule\OrderUpdateAllowed;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Command\UpdateOrder as CommandUpdateOrder;

/**
 * Rules-Layer Guard-Closure for the UpdateOrder command (docs/rules-layer/PLAN.md
 * Vertrag 2). Runs the bound AND-chain with Kurzschluss — the first
 * rejection stops the chain. Rules are dispatched via `$this->handle()`
 * (ClassVersion-fähig), never `new`.
 */
final class GuardUpdateOrder extends EcommerceContext
{
    /**
     * @param CommandUpdateOrder $updateOrder
     * @throws Throwable
     */
    public function __invoke(CommandUpdateOrder $updateOrder): RuleResult
    {
        $result = $this->handle(OrderUpdateAllowed::class)($updateOrder);
        if (!$result->passed) {
            return $result;
        }

        return RuleResult::pass();
    }
}

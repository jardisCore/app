<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Rule\Guard;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Rule\Data\RuleResult;
use Ecommerce\Sales\Rule\OrderIsCancellable;
use Ecommerce\Sales\Rule\StockAvailable;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Command\Order as CommandOrder;

/**
 * Rules-Layer Guard-Closure for the Order command (docs/rules-layer/PLAN.md
 * Vertrag 2). Runs the bound AND-chain with Kurzschluss — the first
 * rejection stops the chain. Rules are dispatched via `$this->handle()`
 * (ClassVersion-fähig), never `new`.
 */
final class GuardOrder extends EcommerceContext
{
    /**
     * @param CommandOrder $order
     * @throws Throwable
     */
    public function __invoke(CommandOrder $order): RuleResult
    {
        $result = $this->handle(OrderIsCancellable::class)($order);
        if (!$result->passed) {
            return $result;
        }

        $result = $this->handle(StockAvailable::class)($order);
        if (!$result->passed) {
            return $result;
        }

        return RuleResult::pass();
    }
}

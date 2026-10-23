<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Closure\Guard;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Closure\OrderIsCancellable;
use Ecommerce\Sales\Closure\OrderUpdateAllowed;
use Ecommerce\Sales\Closure\OrderUpdateConsistent;
use Ecommerce\Sales\Model\Order\Aggregate\Entity\Order;
use JardisSupport\Response\Data\RuleResult;
use Throwable;
use Ecommerce\Sales\Model\Order\Command\UpdateOrder as CommandUpdateOrder;

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
     * @param Order $root
     * @throws Throwable
     */
    public function __invoke(CommandUpdateOrder $updateOrder, Order $root): RuleResult
    {
        $result = $this->handle(OrderIsCancellable::class)($root);
        if (!$result->passed) {
            return RuleResult::reject($result->rule, 'sales.orderAlreadyCancelled', $result->context);
        }

        $result = $this->handle(OrderUpdateAllowed::class)($updateOrder);
        if (!$result->passed) {
            return $result;
        }

        $result = $this->handle(OrderUpdateConsistent::class)($updateOrder, $root);
        if (!$result->passed) {
            return RuleResult::reject($result->rule, 'sales.orderUpdateInconsistent', $result->context);
        }

        return RuleResult::pass();
    }
}

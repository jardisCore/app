<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Closure\Guard;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Closure\Data\RuleResult;
use Ecommerce\Sales\Closure\OrderIsCancellable;
use Ecommerce\Sales\Model\Order\Aggregate\Entity\Order;
use Throwable;
use Ecommerce\Sales\Model\Order\Command\UpdateOrderItem as CommandUpdateOrderItem;

/**
 * Rules-Layer Guard-Closure for the UpdateOrderItem command (docs/rules-layer/PLAN.md
 * Vertrag 2). Runs the bound AND-chain with Kurzschluss — the first
 * rejection stops the chain. Rules are dispatched via `$this->handle()`
 * (ClassVersion-fähig), never `new`.
 */
final class GuardUpdateOrderItem extends EcommerceContext
{
    /**
     * @param CommandUpdateOrderItem $updateOrderItem
     * @param Order|null $root
     * @throws \RuntimeException
     * @throws Throwable
     */
    public function __invoke(CommandUpdateOrderItem $updateOrderItem, ?Order $root = null): RuleResult
    {
        if ($root === null) {
            throw new \RuntimeException(
                'GuardUpdateOrderItem expects the CommandHandler to hand in the already-loaded Order root for its '
                . 'aggregate-subject rule chain — it never loads it itself.'
            );
        }

        $result = $this->handle(OrderIsCancellable::class)($root);
        if (!$result->passed) {
            return RuleResult::reject($result->rule, 'sales.orderAlreadyCancelled', $result->context);
        }

        return RuleResult::pass();
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Closure\Guard;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Closure\Data\RuleResult;
use Ecommerce\Sales\Closure\OrderEligibleForProcessing;
use Ecommerce\Sales\Closure\OrderWithinCreditLimit;
use Ecommerce\Sales\Closure\StockAvailable;
use Throwable;
use Ecommerce\Sales\Model\Order\Command\Order as CommandOrder;

/**
 * Rules-Layer Guard-Closure for the Order command (docs/rules-layer/PLAN.md
 * Vertrag 2). Runs the bound AND-chain with Kurzschluss — the first
 * rejection stops the chain. Rules are dispatched via `$this->handle()`
 * (ClassVersion-fähig), never `new`.
 *
 * Set OrderIntakeChecks — Buendelt die Bestandspruefung und die Verarbeitungsberechtigung als EIN Rule-Set (rule-sets
 * PLAN.md E1-P2, S7/S8/S9 Golden-Fixture).
 * - StockAvailable
 * - OrderEligibleForProcessing
 */
final class GuardOrder extends EcommerceContext
{
    /**
     * @param CommandOrder $order
     * @throws Throwable
     */
    public function __invoke(CommandOrder $order): RuleResult
    {
        $result = $this->handle(StockAvailable::class)($order);
        if (!$result->passed) {
            return RuleResult::reject(
                $result->rule,
                $result->messageKey,
                $result->context + ['set' => 'OrderIntakeChecks']
            );
        }

        $result = $this->handle(OrderEligibleForProcessing::class)($order);
        if (!$result->passed) {
            return RuleResult::reject(
                $result->rule,
                $result->messageKey,
                $result->context + ['set' => 'OrderIntakeChecks']
            );
        }

        $result = $this->handle(OrderWithinCreditLimit::class)($order);
        if (!$result->passed) {
            return $result;
        }

        return RuleResult::pass();
    }
}

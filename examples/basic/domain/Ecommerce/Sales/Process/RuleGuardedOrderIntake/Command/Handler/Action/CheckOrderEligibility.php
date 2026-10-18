<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Closure\OrderEligibleForProcessing;
use Ecommerce\Sales\Closure\StockAvailable;
use Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\Handler\RuleGuardedOrderIntakeContext;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Data\Route;
use Throwable;

/**
 * Node CheckOrderEligibility of the process RuleGuardedOrderIntake (rule).
 *
 * Rule-Knoten (A6, S9): prueft das Rule-Set OrderIntakeChecks
 * (Mitglieder StockAvailable, OrderEligibleForProcessing) ueber den
 * generierten Adapter, bevor der teure Aggregat-Aufruf ueberhaupt
 * startet.
 *
 * Evaluates the rules StockAvailable, OrderEligibleForProcessing.
 */
final class CheckOrderEligibility extends EcommerceContext
{
    /**
     * @node-id n1
     * @throws Throwable
     */
    public function __invoke(RuleGuardedOrderIntakeContext $ctx): NodeResult
    {
        $result = $this->handle(StockAvailable::class)($ctx->input()->order);
        if (!$result->passed) {
            return NodeResult::reject(
                ResponseStatus::RuleViolation,
                $result->messageKey,
                ['rule' => $result->rule] + $result->context + ['set' => 'OrderIntakeChecks']
            );
        }

        $result = $this->handle(OrderEligibleForProcessing::class)($ctx->input()->order);
        if (!$result->passed) {
            return NodeResult::reject(
                ResponseStatus::RuleViolation,
                $result->messageKey,
                ['rule' => $result->rule] + $result->context + ['set' => 'OrderIntakeChecks']
            );
        }

        return NodeResult::route(Route::OnTrue);
    }
}

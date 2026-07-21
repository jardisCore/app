<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\RuleGuardedOrderIntake;
use Ecommerce\Sales\Rule\OrderEligibleForProcessing;
use JardisSupport\Contract\Workflow\WorkflowContextInterface;
use JardisSupport\Contract\Workflow\WorkflowResultInterface;
use JardisSupport\Workflow\WorkflowResult;
use Throwable;

/**
 * Rule-Knoten (A6): prueft die ungebundene Katalog-Rule
 * OrderEligibleForProcessing ueber den generierten Adapter, bevor der
 * teure Aggregat-Aufruf ueberhaupt startet.
 *
 * Status: onSuccess | onFail
 *
 * Rule-Knoten (docs/rules-layer/PLAN.md A6): prueft die Katalog-Rule OrderEligibleForProcessing
 * ueber die Kernel-Naht ($this->handle()) — passed => ON_SUCCESS, rejected
 * => ON_FAIL. Generierter Adapter, kein Entwickler-Body (ForceOverwrite).
 */
final class CheckOrderEligibility extends EcommerceContext
{
    /**
     * @node-id n1
     * @throws Throwable
     */
    public function __invoke(WorkflowContextInterface $context): WorkflowResultInterface
    {
        /** @var RuleGuardedOrderIntake $cmd */
        $cmd = $this->payload();

        $result = $this->handle(OrderEligibleForProcessing::class)($cmd);

        if ($result->passed) {
            return new WorkflowResult(WorkflowResult::ON_SUCCESS, []);
        }

        return new WorkflowResult(WorkflowResult::ON_FAIL, [
            'rule'       => $result->rule,
            'messageKey' => $result->messageKey,
            'context'    => $result->context,
        ]);
    }
}

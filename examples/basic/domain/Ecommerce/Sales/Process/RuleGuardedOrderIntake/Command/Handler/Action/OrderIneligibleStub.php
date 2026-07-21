<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\RuleGuardedOrderIntake;
use JardisSupport\Contract\Workflow\WorkflowContextInterface;
use JardisSupport\Contract\Workflow\WorkflowResultInterface;
use JardisSupport\Workflow\WorkflowResult;
use RuntimeException;

/**
 * onFail-Zweig des Rule-Knotens (fruehe Ablehnung). Bleibt bewusst ein
 * Wurf-Stub (kein consumedCall) — KI-/Dev-Hoheit.
 *
 * Hilfen aus dem Workflow-Context (siehe WorkflowContextInterface):
 *  - $cmd       = $this->payload();                       // Initial-Params (Command-DTO)
 *  - $prev      = $context->getPrevious();                // direkter Vorgaenger (Status + getData())
 *  - $latest    = $context->getLatest(NodeFqcn::class);   // juengste Invocation eines Knotens
 *  - $allCalls  = $context->getAll(NodeFqcn::class);      // History (Iteration-Counter)
 */
final class OrderIneligibleStub extends EcommerceContext
{
    /**
     * @node-id n3
     * @throws RuntimeException
     */
    public function __invoke(WorkflowContextInterface $context): WorkflowResultInterface
    {
        /** @var RuleGuardedOrderIntake $cmd */
        $cmd = $this->payload();

        $result = $this->logic($cmd, $context);

        return new WorkflowResult($result['status'], $result['data']);
    }

    /**
     * Die fachliche Logik dieses Knotens — von KI/Entwickler gefuellt.
     * Liefert den Ausgangs-Status (siehe Status-Zeile oben) und die
     * Daten fuer den naechsten Knoten; __invoke baut daraus das
     * WorkflowResult.
     *
     * @return array{status: string, data: array<string, mixed>}
     */
    protected function logic(RuleGuardedOrderIntake $cmd, WorkflowContextInterface $context): array
    {
        return ['status' => WorkflowResult::ON_SUCCESS, 'data' => []];
    }
}

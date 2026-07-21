<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Order\Order;
use Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\RuleGuardedOrderIntake;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Contract\Workflow\WorkflowContextInterface;
use JardisSupport\Contract\Workflow\WorkflowResultInterface;
use JardisSupport\Workflow\WorkflowResult;
use RuntimeException;
use Throwable;

/**
 * Ruft den Order-Aggregat-Command Order (createOrder) auf. onFail bei
 * 422 (Rule-Ablehnung der Order-eigenen Kette, Vertrag 5) — ein
 * technischer 5xx-Fehler geht NICHT ueber diese Kante, sondern in den
 * Exception-Pfad der Engine.
 *
 * Status: onFail
 *
 * Hilfen aus dem Workflow-Context (siehe WorkflowContextInterface):
 *  - $cmd       = $this->payload();                       // Initial-Params (Command-DTO)
 *  - $prev      = $context->getPrevious();                // direkter Vorgaenger (Status + getData())
 *  - $latest    = $context->getLatest(NodeFqcn::class);   // juengste Invocation eines Knotens
 *  - $allCalls  = $context->getAll(NodeFqcn::class);      // History (Iteration-Counter)
 */
final class PlaceOrder extends EcommerceContext
{
    /**
     * @node-id n2
     * @throws RuntimeException
     * @throws Throwable
     */
    public function __invoke(WorkflowContextInterface $context): WorkflowResultInterface
    {
        /** @var RuleGuardedOrderIntake $cmd */
        $cmd = $this->payload();

        $result = $this->logic($cmd, $context);

        return new WorkflowResult($result['status'], $result['data']);
    }

    /**
     * Ruft den Aggregat-Command direkt ueber die Kernel-Naht auf
     * (familieninterner Zugriff auf die Aggregat-Fassade) und leitet den
     * Routing-Status aus der DomainResponse ab. Vom Generator vorgebackener
     * Default-Body — gegen die Knoten-Requirements pruefen.
     *
     * Statustreppe (docs/rules-layer/PLAN.md Vertrag 5): eine 422-Ablehnung
     * (RuleViolation, fachlicher Fehlschlag) routet ueber die gemalte
     * onFail-Kante — ein 5xx (technischer Fehler) NIE: er wirft, damit der
     * Prozess in den Exception-Pfad der Engine faellt.
     *
     * @return array{status: string, data: array<string, mixed>}
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function logic(RuleGuardedOrderIntake $cmd, WorkflowContextInterface $context): array
    {
        // Generierter Default-Body — gegen die Knoten-Requirements pruefen,
        // erweitern oder ersetzen.
        /** @var DomainResponseInterface $response */
        $response = $this->handle(Order::class)->createOrder($cmd->order);

        if ($response->getStatus() >= ResponseStatus::InternalError->value) {
            throw new \RuntimeException(sprintf('Technischer Fehler bei createOrder (Status %d).', $response->getStatus()));
        }

        return [
            'status' => $response->isSuccess() ? WorkflowResult::ON_SUCCESS : WorkflowResult::ON_FAIL,
            'data'   => $response->getData(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\ExternalCallServiceDemo;
use JardisSupport\Contract\Workflow\WorkflowContextInterface;
use JardisSupport\Contract\Workflow\WorkflowResultInterface;
use JardisSupport\Workflow\WorkflowResult;
use RuntimeException;

/**
 * Platzhalter-Abschluss (onFail-Zweig) — bleibt bewusst ein Wurf-Stub
 * (kein consumedCall/externalCall) — KI-/Dev-Hoheit.
 *
 * Hilfen aus dem Workflow-Context (siehe WorkflowContextInterface):
 *  - $cmd       = $this->payload();                       // Initial-Params (Command-DTO)
 *  - $prev      = $context->getPrevious();                // direkter Vorgaenger (Status + getData())
 *  - $latest    = $context->getLatest(NodeFqcn::class);   // juengste Invocation eines Knotens
 *  - $allCalls  = $context->getAll(NodeFqcn::class);      // History (Iteration-Counter)
 */
final class ExternalCallServiceDemoFinalize extends EcommerceContext
{
    /**
     * @node-id e4d5e604
     * @throws RuntimeException
     */
    public function __invoke(WorkflowContextInterface $context): WorkflowResultInterface
    {
        /** @var ExternalCallServiceDemo $cmd */
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
     * @throws RuntimeException
     */
    protected function logic(ExternalCallServiceDemo $cmd, WorkflowContextInterface $context): array
    {
        throw new \RuntimeException(
            'Not implemented: write a mini-PRD or body for ' . self::class
        );
    }
}

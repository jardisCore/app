<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\OrderInvoiceBatch;
use JardisSupport\Contract\Workflow\WorkflowContextInterface;
use JardisSupport\Contract\Workflow\WorkflowResultInterface;
use JardisSupport\Workflow\WorkflowResult;
use RuntimeException;

/**
 * @todo iteration-pending
 * Verarbeitet die typisierten Commands. Die Fan-out-Dispatch-Schleife
 * (foreach … match(instanceof)) ist Dev-Code und lebt hier im logic(),
 * nicht im generierten DTO.
 *
 * Zweck: Wendet die komponierten Aggregat-Commands transaktional an.
 *
 * Fertig wenn:
 *  - [ ] Einzel-Command und Listen-Commands werden dispatcht
 *  - [ ] Fehler routet nach onFail
 *
 * Status: onSuccess
 *
 * Hilfen aus dem Workflow-Context (siehe WorkflowContextInterface):
 *  - $cmd       = $this->payload();                       // Initial-Params (Command-DTO)
 *  - $prev      = $context->getPrevious();                // direkter Vorgaenger (Status + getData())
 *  - $latest    = $context->getLatest(NodeFqcn::class);   // juengste Invocation eines Knotens
 *  - $allCalls  = $context->getAll(NodeFqcn::class);      // History (Iteration-Counter)
 */
final class ApplyBatch extends EcommerceContext
{
    /**
     * @node-id b2c3d402
     * @throws RuntimeException
     */
    public function __invoke(WorkflowContextInterface $context): WorkflowResultInterface
    {
        /** @var OrderInvoiceBatch $cmd */
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
    protected function logic(OrderInvoiceBatch $cmd, WorkflowContextInterface $context): array
    {
        throw new \RuntimeException(
            'Not implemented: write a mini-PRD or body for ' . self::class
        );
    }
}

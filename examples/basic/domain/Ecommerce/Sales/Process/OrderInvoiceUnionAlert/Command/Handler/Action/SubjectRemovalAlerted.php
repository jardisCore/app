<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Invoice\Command\RemoveInvoice;
use Ecommerce\Sales\Aggregate\Order\Command\RemoveOrder;
use Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\OrderInvoiceUnionAlert;
use Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Event\SubjectRemovalAlerted;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Workflow\WorkflowContextInterface;
use JardisSupport\Contract\Workflow\WorkflowResultInterface;
use JardisSupport\Data\Identity;
use JardisSupport\Workflow\WorkflowResult;
use Throwable;

/**
 * Event-Kasten ◇ — verkuendet das Domain-Event SubjectRemovalAlerted.
 *
 * Hilfen aus dem Workflow-Context (siehe WorkflowContextInterface):
 *  - $cmd       = $this->payload();                       // Initial-Params (Command-DTO)
 *  - $prev      = $context->getPrevious();                // direkter Vorgaenger (Status + getData())
 *  - $latest    = $context->getLatest(NodeFqcn::class);   // juengste Invocation eines Knotens
 *  - $allCalls  = $context->getAll(NodeFqcn::class);      // History (Iteration-Counter)
 */
final class SubjectRemovalAlertedNode extends EcommerceContext
{
    /**
     * @node-id v2b3c402
     * @throws Throwable
     */
    public function __invoke(WorkflowContextInterface $context): WorkflowResultInterface
    {
        /** @var OrderInvoiceUnionAlert $cmd */
        $cmd = $this->payload();

        $result = $this->logic($cmd, $context);

        return new WorkflowResult($result['status'], $result['data']);
    }

    /**
     * Verkuendet das Domain-Event SubjectRemovalAlerted.
     * Das Event reist im data-Frachtraum unter EventScope::Domain->value;
     * der Orchestrator erntet es nach dem Lauf aus der Kette.
     *
     * @return array{status: string, data: array<string, mixed>}
     * @throws Throwable
     */
    protected function logic(OrderInvoiceUnionAlert $cmd, WorkflowContextInterface $context): array
    {
        return [
            'status' => WorkflowResult::ON_SUCCESS,
            'data'   => [
                EventScope::Domain->value => [
                    new SubjectRemovalAlerted(
                        removedOrderId: match (true) {
                            $cmd->subjectRemoval instanceof RemoveOrder => $cmd->subjectRemoval->orderNumber,
                            $cmd->subjectRemoval instanceof RemoveInvoice => $cmd->subjectRemoval->invoiceIdentifier,
                        },
                        eventId: $this->handle(Identity::class)->generateUuid7(),
                        occurredAt: new \DateTimeImmutable(),
                    ),
                ],
            ],
        ];
    }
}

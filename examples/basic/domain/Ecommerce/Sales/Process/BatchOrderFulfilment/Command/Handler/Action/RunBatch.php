<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\BatchOrderFulfilment\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Invoice\Command\AddInvoiceLine;
use Ecommerce\Sales\Aggregate\Invoice\Command\UpdateInvoice;
use Ecommerce\Sales\Aggregate\Order\Command\AddOrderItem;
use Ecommerce\Sales\Aggregate\Order\Command\UpdateOrder;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler\OrderInvoiceBatchHandler;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\OrderInvoiceBatch;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Workflow\WorkflowContextInterface;
use JardisSupport\Contract\Workflow\WorkflowResultInterface;
use JardisSupport\Workflow\WorkflowResult;
use RuntimeException;

/**
 * Sub-Process-Knoten — ruft den Prozess OrderInvoiceBatch synchron auf (EVA-im-EVA).
 *
 * Der Knoten baut das Start-DTO des Sub-Prozesses aus dem Lauf-Zustand
 * ($this->payload() = Haupt-DTO, $context = vorherige Knoten-Ergebnisse),
 * ruft ihn ueber die Kernel-Naht $this->context(...) auf, leitet den
 * Routing-Status aus dessen Ergebnis ab und laesst dessen Domain-Events
 * flach nach oben blubbern (verkuendet wird einmal ganz oben nach Commit).
 *
 * Status: onSuccess | onFail
 *
 * Hilfen aus dem Workflow-Context (siehe WorkflowContextInterface):
 *  - $cmd       = $this->payload();                       // Haupt-Prozess-DTO
 *  - $prev      = $context->getPrevious();                // direkter Vorgaenger (Status + getData())
 *  - $latest    = $context->getLatest(NodeFqcn::class);   // juengste Invocation eines Knotens
 */
final class RunBatch extends EcommerceContext
{
    /**
     * @node-id f1b2c302
     * @throws RuntimeException
     */
    public function __invoke(WorkflowContextInterface $context): WorkflowResultInterface
    {
        $result = $this->logic($context);

        return new WorkflowResult($result['status'], $result['data']);
    }

    /**
     * Ruft den Sub-Prozess OrderInvoiceBatch synchron auf, mappt den Status und
     * sammelt dessen Domain-Events flach ein. Vom Generator vorgebacken;
     * KI/Entwickler fuellt nur die DTO-Werte (siehe Resolver unten).
     *
     * @return array{status: string, data: array<string, mixed>}
     * @throws RuntimeException
     */
    protected function logic(WorkflowContextInterface $context): array
    {
        $in = new OrderInvoiceBatch(
            note: $this->note($context),
            requestedBy: $this->requestedBy($context),
            primaryCommand: $this->primaryCommand($context),
            lineCommands: $this->lineCommands($context),
        );

        $res = $this->context(OrderInvoiceBatchHandler::class, $in)();

        $events = [];
        foreach ($res->getEvents(EventScope::Domain) as $originEvents) {
            foreach ($originEvents as $event) {
                $events[] = $event;
            }
        }

        return [
            'status' => $res->isSuccess() ? WorkflowResult::ON_SUCCESS : WorkflowResult::ON_FAIL,
            'data'   => [
                EventScope::Domain->value => $events,
            ],
        ];
    }

    /**
     * Liefert den Wert fuer das Sub-DTO-Feld 'note'.
     * KI/Entwickler: throw ersetzen durch den Wert aus $this->payload()/$context.
     * @throws RuntimeException
     */
    protected function note(WorkflowContextInterface $context): string
    {
        throw new \RuntimeException('Sub-DTO-Feld note fuellen: ' . self::class);
    }

    /**
     * Liefert den Wert fuer das Sub-DTO-Feld 'requestedBy'.
     * KI/Entwickler: throw ersetzen durch den Wert aus $this->payload()/$context.
     * @throws RuntimeException
     */
    protected function requestedBy(WorkflowContextInterface $context): string
    {
        throw new \RuntimeException('Sub-DTO-Feld requestedBy fuellen: ' . self::class);
    }

    /**
     * Liefert den Wert fuer das Sub-DTO-Feld 'primaryCommand'.
     * KI/Entwickler: throw ersetzen durch den Wert aus $this->payload()/$context.
     * @throws RuntimeException
     */
    protected function primaryCommand(WorkflowContextInterface $context): UpdateOrder|UpdateInvoice
    {
        throw new \RuntimeException('Sub-DTO-Feld primaryCommand fuellen: ' . self::class);
    }

    /**
     * Liefert den Wert fuer das Sub-DTO-Feld 'lineCommands'.
     * KI/Entwickler: throw ersetzen durch den Wert aus $this->payload()/$context.
     * @return array<AddOrderItem|AddInvoiceLine>
     * @throws RuntimeException
     */
    protected function lineCommands(WorkflowContextInterface $context): array
    {
        throw new \RuntimeException('Sub-DTO-Feld lineCommands fuellen: ' . self::class);
    }
}

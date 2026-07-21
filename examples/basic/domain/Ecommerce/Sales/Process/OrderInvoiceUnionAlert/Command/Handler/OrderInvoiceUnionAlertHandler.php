<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\Handler\Action\EvaluateSubjectRemoval;
use Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\Handler\Action\SubjectRemovalAlertedNode;
use Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\OrderInvoiceUnionAlert;
use Exception;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Contract\Kernel\EventScope;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Contract\Workflow\WorkflowConfigInterface;
use JardisSupport\Factory\Factory;
use JardisSupport\Workflow\Workflow;
use JardisSupport\Workflow\WorkflowConfig;
use JardisSupport\Workflow\WorkflowResult;
use Throwable;

/**
 * Workflow orchestrator for OrderInvoiceUnionAlertHandler.
 *
 * Orchestration-Full-Graph:
 * the whole graph IS the workflow — config() registers every node, the
 * engine runs the painted edges; domain events are harvested from the chain.
 */
final class OrderInvoiceUnionAlertHandler extends EcommerceContext
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var OrderInvoiceUnionAlert $cmd */
            $cmd = $this->payload();

            /** @var Factory $container */
            $container = $this->resource()->container();

            $workflow = $container->create(
                Workflow::class,
                /** @phpstan-ignore return.type */
                fn(string $cls, mixed $data = null): object => $data !== null
                    /** @phpstan-ignore argument.type */
                    ? $this->context($cls, $data)
                    : $this->handle($cls),
            );

            $ctx = $workflow($this->config(), $cmd);

            foreach ($ctx->getChain() as $nodeResult) {
                $data = $nodeResult->getData();
                foreach (is_array($data) ? ($data[EventScope::Domain->value] ?? []) : [] as $event) {
                    $this->result()->addEvent($event, EventScope::Domain);
                }
            }

            return $this->handle(DomainResponseTransformer::class)
                ->transform($this->result());
        } catch (Throwable $e) {
            $this->result()->addError($e->getMessage());

            return $this->handle(DomainResponseTransformer::class)
                ->transform($this->result(), ResponseStatus::InternalError);
        }
    }

    private function config(): WorkflowConfigInterface
    {
        return (new WorkflowConfig())
            ->addNode(EvaluateSubjectRemoval::class, [
                WorkflowResult::ON_SUCCESS => SubjectRemovalAlertedNode::class,
            ])
            ->addNode(SubjectRemovalAlertedNode::class, []);
    }
}

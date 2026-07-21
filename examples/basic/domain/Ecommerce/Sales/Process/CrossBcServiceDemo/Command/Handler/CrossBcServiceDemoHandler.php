<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler;

use Ecommerce\EcommerceContext;
use Ecommerce\Response\DomainResponseTransformer;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\CrossBcServiceDemo;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\Action\CheckMeterReadingInMeterDevice;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\Action\CheckStockInCatalog;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\Action\CrossBcServiceDemoFinalize;
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
 * Workflow orchestrator for CrossBcServiceDemoHandler.
 *
 * Orchestration-Full-Graph:
 * the whole graph IS the workflow — config() registers every node, the
 * engine runs the painted edges; domain events are harvested from the chain.
 */
final class CrossBcServiceDemoHandler extends EcommerceContext
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(): DomainResponseInterface
    {
        try {
            /** @var CrossBcServiceDemo $cmd */
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
            ->addNode(CheckStockInCatalog::class, [
                WorkflowResult::ON_FAIL => CheckMeterReadingInMeterDevice::class,
            ])
            ->addNode(CheckMeterReadingInMeterDevice::class, [
                WorkflowResult::ON_SUCCESS => CrossBcServiceDemoFinalize::class,
                WorkflowResult::ON_FAIL => CrossBcServiceDemoFinalize::class,
            ])
            ->addNode(CrossBcServiceDemoFinalize::class, []);
    }
}

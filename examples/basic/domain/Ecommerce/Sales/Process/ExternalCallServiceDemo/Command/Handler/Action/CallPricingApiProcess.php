<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\ExternalCallServiceDemo;
use JardisSupport\Contract\Workflow\WorkflowContextInterface;
use JardisSupport\Contract\Workflow\WorkflowResultInterface;
use JardisSupport\Workflow\WorkflowResult;
use Throwable;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Service\CallPricingApiProcess as CallPricingApiProcessService;

/**
 * Ebene "process" (Default): der Service-Stub landet unter
 * {BC}/Process/{ProcessName}/Service/.
 *
 * Status: onSuccess | onFail
 *
 * Externer-Call (Ebene: process): delegiert an den Service CallPricingApiProcess ueber die
 * Kernel-Naht ($this->handle()) — der PSR-18-Aufruf lebt dort (Dev-Hoheit).
 */
final class CallPricingApiProcess extends EcommerceContext
{
    /**
     * @node-id e1a2b301
     * @throws Throwable
     */
    public function __invoke(WorkflowContextInterface $context): WorkflowResultInterface
    {
        /** @var ExternalCallServiceDemo $cmd */
        $cmd = $this->payload();

        $result = $this->logic($cmd, $context);

        return new WorkflowResult($result['status'], $result['data']);
    }

    /**
     * Delegiert an den generierten Service-Stub CallPricingApiProcess.
     *
     * @return array{status: string, data: array<string, mixed>}
     * @throws Throwable
     */
    protected function logic(ExternalCallServiceDemo $cmd, WorkflowContextInterface $context): array
    {
        return $this->handle(CallPricingApiProcessService::class)->__invoke($context);
    }
}

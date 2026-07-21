<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\ExternalCallServiceDemo;
use JardisSupport\Contract\Workflow\WorkflowContextInterface;
use JardisSupport\Contract\Workflow\WorkflowResultInterface;
use JardisSupport\Workflow\WorkflowResult;
use Throwable;
use Ecommerce\Sales\Service\CallPricingApiBc as CallPricingApiBcService;

/**
 * Ebene "bc": der Service-Stub landet unter {Domain}/{BC}/Service/.
 *
 * Status: onSuccess | onFail
 *
 * Externer-Call (Ebene: bc): delegiert an den Service CallPricingApiBc ueber die
 * Kernel-Naht ($this->handle()) — der PSR-18-Aufruf lebt dort (Dev-Hoheit).
 */
final class CallPricingApiBc extends EcommerceContext
{
    /**
     * @node-id e2b3c402
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
     * Delegiert an den generierten Service-Stub CallPricingApiBc.
     *
     * @return array{status: string, data: array<string, mixed>}
     * @throws Throwable
     */
    protected function logic(ExternalCallServiceDemo $cmd, WorkflowContextInterface $context): array
    {
        return $this->handle(CallPricingApiBcService::class)->__invoke($context);
    }
}

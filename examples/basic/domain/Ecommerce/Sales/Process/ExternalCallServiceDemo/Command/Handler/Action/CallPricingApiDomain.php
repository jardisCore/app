<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\ExternalCallServiceDemo;
use JardisSupport\Contract\Workflow\WorkflowContextInterface;
use JardisSupport\Contract\Workflow\WorkflowResultInterface;
use JardisSupport\Workflow\WorkflowResult;
use Throwable;
use Ecommerce\Service\CallPricingApiDomain as CallPricingApiDomainService;

/**
 * Ebene "domain": der Service-Stub landet unter Domain/{Domain}/Service/.
 *
 * Status: onSuccess | onFail
 *
 * Externer-Call (Ebene: domain): delegiert an den Service CallPricingApiDomain ueber die
 * Kernel-Naht ($this->handle()) — der PSR-18-Aufruf lebt dort (Dev-Hoheit).
 */
final class CallPricingApiDomain extends EcommerceContext
{
    /**
     * @node-id e3c4d503
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
     * Delegiert an den generierten Service-Stub CallPricingApiDomain.
     *
     * @return array{status: string, data: array<string, mixed>}
     * @throws Throwable
     */
    protected function logic(ExternalCallServiceDemo $cmd, WorkflowContextInterface $context): array
    {
        return $this->handle(CallPricingApiDomainService::class)->__invoke($context);
    }
}

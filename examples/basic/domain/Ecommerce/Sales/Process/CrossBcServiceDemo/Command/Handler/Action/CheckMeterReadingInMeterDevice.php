<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\CrossBcServiceDemo;
use JardisSupport\Contract\Workflow\WorkflowContextInterface;
use JardisSupport\Contract\Workflow\WorkflowResultInterface;
use JardisSupport\Workflow\WorkflowResult;
use Throwable;
use Ecommerce\Service\CheckMeterReadingInMeterDevice as CheckMeterReadingInMeterDeviceService;

/**
 * Cross-Domain-Variante (D3): schreibt in die fremde BC Counter in der
 * fremden Domain MeterDevice ueber deren Prozess RecordCounterUpdate (G7),
 * nicht direkt ueber das Aggregat. Der Domain-Service uebersetzt die
 * eigene Eingabe in das fremde Prozess-Input-DTO (ACL).
 *
 * Status: onSuccess | onFail
 *
 * Cross-BC-Call: delegiert an den Service CheckMeterReadingInMeterDevice ueber die Kernel-Naht
 * ($this->handle()) — die eigentliche Uebersetzung lebt dort (Dev-Hoheit).
 */
final class CheckMeterReadingInMeterDevice extends EcommerceContext
{
    /**
     * @node-id a2b3c402
     * @throws Throwable
     */
    public function __invoke(WorkflowContextInterface $context): WorkflowResultInterface
    {
        /** @var CrossBcServiceDemo $cmd */
        $cmd = $this->payload();

        $result = $this->logic($cmd, $context);

        return new WorkflowResult($result['status'], $result['data']);
    }

    /**
     * Delegiert an den generierten Service-Stub CheckMeterReadingInMeterDevice.
     *
     * @return array{status: string, data: array<string, mixed>}
     * @throws Throwable
     */
    protected function logic(CrossBcServiceDemo $cmd, WorkflowContextInterface $context): array
    {
        return $this->handle(CheckMeterReadingInMeterDeviceService::class)->__invoke($context);
    }
}

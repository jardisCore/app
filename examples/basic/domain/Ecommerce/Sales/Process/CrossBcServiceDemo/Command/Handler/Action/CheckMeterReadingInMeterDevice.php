<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\CrossBcServiceDemoContext;
use JardisSupport\Workflow\Data\NodeResult;
use Throwable;
use Ecommerce\Service\CheckMeterReadingInMeterDevice as CheckMeterReadingInMeterDeviceService;

/**
 * Node CheckMeterReadingInMeterDevice of the process CrossBcServiceDemo (call).
 *
 * Cross-Domain-Variante (D3): schreibt in die fremde BC Counter in der
 * fremden Domain MeterDevice ueber deren Prozess RecordCounterUpdate (G7),
 * nicht direkt ueber das Aggregat. Der Domain-Service uebersetzt die
 * eigene Eingabe in das fremde Prozess-Input-DTO (ACL).
 *
 * Calls the facade MeterDevice.Counter.RecordCounterUpdate, method recordCounterUpdate.
 */
final class CheckMeterReadingInMeterDevice extends EcommerceContext
{
    /**
     * @node-id a2b3c402
     * @throws Throwable
     */
    public function __invoke(CrossBcServiceDemoContext $ctx): NodeResult
    {
        return $this->handle(CheckMeterReadingInMeterDeviceService::class)($ctx);
    }
}

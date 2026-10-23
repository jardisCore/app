<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler;

use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\CrossBcServiceDemo;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\Action\CheckMeterReadingInMeterDevice;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\Action\CheckStockInCatalog;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\Action\CrossBcServiceDemoFinalize;
use JardisSupport\Workflow\Data\ProcessDefinition;
use JardisSupport\Workflow\Data\Route;

/**
 * Definition of the process CrossBcServiceDemo: the graph, its routes and the per-node declarations.
 *
 * Hermetic - generated from the process model, rebuilt on every build.
 */
final class CrossBcServiceDemoDefinition
{
    public function __invoke(): ProcessDefinition
    {
        return ProcessDefinition::for(CrossBcServiceDemo::class, 'CrossBcServiceDemo')
            ->context(CrossBcServiceDemoContext::class)
            ->transactional(true)
            ->node(CheckStockInCatalog::class)
            ->onFalse(CheckMeterReadingInMeterDevice::class)
            ->terminal(Route::OnTrue)
            ->node(CheckMeterReadingInMeterDevice::class)
            ->onTrue(CrossBcServiceDemoFinalize::class)
            ->node(CrossBcServiceDemoFinalize::class)
            ->terminal(Route::OnTrue)
            ->build();
    }
}

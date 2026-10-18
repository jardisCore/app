<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler;

use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\CrossBcServiceDemo;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\Action\CheckMeterReadingInMeterDevice;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\Action\CheckStockInCatalog;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\Action\CrossBcServiceDemoFinalize;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Data\ProcessContext;

/**
 * Read view of a run of the process CrossBcServiceDemo: its input and one typed getter per node.
 *
 * Hermetic - the getters follow the nodes of the process model.
 */
final class CrossBcServiceDemoContext extends ProcessContext
{
    public function input(): CrossBcServiceDemo
    {
        /** @var CrossBcServiceDemo $input */
        $input = parent::input();

        return $input;
    }

    public function checkStockInCatalog(): ?NodeResult
    {
        return $this->latest(CheckStockInCatalog::class);
    }

    public function checkMeterReadingInMeterDevice(): ?NodeResult
    {
        return $this->latest(CheckMeterReadingInMeterDevice::class);
    }

    public function crossBcServiceDemoFinalize(): ?NodeResult
    {
        return $this->latest(CrossBcServiceDemoFinalize::class);
    }
}

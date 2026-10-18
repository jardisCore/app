<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\CrossBcReadDemo\Command\Handler;

use Ecommerce\Sales\Process\CrossBcReadDemo\Command\CrossBcReadDemo;
use Ecommerce\Sales\Process\CrossBcReadDemo\Command\Handler\Action\LoadCatalogProduct;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Data\ProcessContext;

/**
 * Read view of a run of the process CrossBcReadDemo: its input and one typed getter per node.
 *
 * Hermetic - the getters follow the nodes of the process model.
 */
final class CrossBcReadDemoContext extends ProcessContext
{
    public function input(): CrossBcReadDemo
    {
        /** @var CrossBcReadDemo $input */
        $input = parent::input();

        return $input;
    }

    public function loadCatalogProduct(): ?NodeResult
    {
        return $this->latest(LoadCatalogProduct::class);
    }
}

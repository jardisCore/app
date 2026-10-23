<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\CrossBcReadDemo\Command\Handler;

use Ecommerce\Sales\Process\CrossBcReadDemo\Command\CrossBcReadDemo;
use Ecommerce\Sales\Process\CrossBcReadDemo\Command\Handler\Action\LoadCatalogProduct;
use JardisSupport\Workflow\Data\ProcessDefinition;
use JardisSupport\Workflow\Data\Route;

/**
 * Definition of the process CrossBcReadDemo: the graph, its routes and the per-node declarations.
 *
 * Hermetic - generated from the process model, rebuilt on every build.
 */
final class CrossBcReadDemoDefinition
{
    public function __invoke(): ProcessDefinition
    {
        return ProcessDefinition::for(CrossBcReadDemo::class, 'CrossBcReadDemo')
            ->context(CrossBcReadDemoContext::class)
            ->transactional(true)
            ->node(LoadCatalogProduct::class)
            ->terminal(Route::OnTrue)
            ->build();
    }
}

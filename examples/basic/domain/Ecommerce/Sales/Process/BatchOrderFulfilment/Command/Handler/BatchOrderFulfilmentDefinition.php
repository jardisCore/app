<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\BatchOrderFulfilment\Command\Handler;

use Ecommerce\Sales\Process\BatchOrderFulfilment\Command\BatchOrderFulfilment;
use Ecommerce\Sales\Process\BatchOrderFulfilment\Command\Handler\Action\BatchOrderFulfilmentEntry;
use Ecommerce\Sales\Process\BatchOrderFulfilment\Command\Handler\Action\BatchOrderFulfilmentFinalize;
use Ecommerce\Sales\Process\BatchOrderFulfilment\Command\Handler\Action\RunBatch;
use JardisSupport\Workflow\Data\ProcessDefinition;
use JardisSupport\Workflow\Data\Route;

/**
 * Definition of the process BatchOrderFulfilment: the graph, its routes and the per-node declarations.
 *
 * Hermetic - generated from the process model, rebuilt on every build.
 */
final class BatchOrderFulfilmentDefinition
{
    public function __invoke(): ProcessDefinition
    {
        return ProcessDefinition::for(BatchOrderFulfilment::class, 'BatchOrderFulfilment')
            ->context(BatchOrderFulfilmentContext::class)
            ->transactional(true)
            ->validate(BatchOrderFulfilmentValidator::class)
            ->node(BatchOrderFulfilmentEntry::class)
            ->onTrue(RunBatch::class)
            ->node(RunBatch::class)
            ->onTrue(BatchOrderFulfilmentFinalize::class)
            ->node(BatchOrderFulfilmentFinalize::class)
            ->terminal(Route::OnTrue)
            ->build();
    }
}

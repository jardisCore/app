<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\BatchOrderFulfilment\Command\Handler;

use Ecommerce\Sales\Process\BatchOrderFulfilment\Command\BatchOrderFulfilment;
use Ecommerce\Sales\Process\BatchOrderFulfilment\Command\Handler\Action\BatchOrderFulfilmentEntry;
use Ecommerce\Sales\Process\BatchOrderFulfilment\Command\Handler\Action\BatchOrderFulfilmentFinalize;
use Ecommerce\Sales\Process\BatchOrderFulfilment\Command\Handler\Action\RunBatch;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Data\ProcessContext;

/**
 * Read view of a run of the process BatchOrderFulfilment: its input and one typed getter per node.
 *
 * Hermetic - the getters follow the nodes of the process model.
 */
final class BatchOrderFulfilmentContext extends ProcessContext
{
    public function input(): BatchOrderFulfilment
    {
        /** @var BatchOrderFulfilment $input */
        $input = parent::input();

        return $input;
    }

    public function batchOrderFulfilmentEntry(): ?NodeResult
    {
        return $this->latest(BatchOrderFulfilmentEntry::class);
    }

    public function runBatch(): ?NodeResult
    {
        return $this->latest(RunBatch::class);
    }

    public function batchOrderFulfilmentFinalize(): ?NodeResult
    {
        return $this->latest(BatchOrderFulfilmentFinalize::class);
    }
}

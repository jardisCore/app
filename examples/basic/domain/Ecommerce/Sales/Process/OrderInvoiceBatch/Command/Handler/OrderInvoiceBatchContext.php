<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler;

use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler\Action\ApplyBatch;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler\Action\OrderInvoiceBatchEntry;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler\Action\OrderInvoiceBatchFinalize;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\OrderInvoiceBatch;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Data\ProcessContext;

/**
 * Read view of a run of the process OrderInvoiceBatch: its input and one typed getter per node.
 *
 * Hermetic - the getters follow the nodes of the process model.
 */
final class OrderInvoiceBatchContext extends ProcessContext
{
    public function input(): OrderInvoiceBatch
    {
        /** @var OrderInvoiceBatch $input */
        $input = parent::input();

        return $input;
    }

    public function orderInvoiceBatchEntry(): ?NodeResult
    {
        return $this->latest(OrderInvoiceBatchEntry::class);
    }

    public function applyBatch(): ?NodeResult
    {
        return $this->latest(ApplyBatch::class);
    }

    public function orderInvoiceBatchFinalize(): ?NodeResult
    {
        return $this->latest(OrderInvoiceBatchFinalize::class);
    }
}

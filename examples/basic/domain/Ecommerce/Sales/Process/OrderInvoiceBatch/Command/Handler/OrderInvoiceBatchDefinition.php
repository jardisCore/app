<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler;

use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler\Action\ApplyBatch;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler\Action\OrderInvoiceBatchEntry;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler\Action\OrderInvoiceBatchFinalize;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\OrderInvoiceBatch;
use JardisSupport\Workflow\Data\ProcessDefinition;
use JardisSupport\Workflow\Data\Route;

/**
 * Definition of the process OrderInvoiceBatch: the graph, its routes and the per-node declarations.
 *
 * Hermetic - generated from the process model, rebuilt on every build.
 */
final class OrderInvoiceBatchDefinition
{
    public function __invoke(): ProcessDefinition
    {
        return ProcessDefinition::for(OrderInvoiceBatch::class, 'OrderInvoiceBatch')
            ->context(OrderInvoiceBatchContext::class)
            ->transactional(true)
            ->validate(OrderInvoiceBatchValidator::class)
            ->node(OrderInvoiceBatchEntry::class)
            ->onTrue(ApplyBatch::class)
            ->node(ApplyBatch::class)
            ->onTrue(OrderInvoiceBatchFinalize::class)
            ->node(OrderInvoiceBatchFinalize::class)
            ->terminal(Route::OnTrue)
            ->build();
    }
}

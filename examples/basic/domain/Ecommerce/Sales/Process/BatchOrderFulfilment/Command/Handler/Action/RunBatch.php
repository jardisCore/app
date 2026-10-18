<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\BatchOrderFulfilment\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\BatchOrderFulfilment\Command\Handler\BatchOrderFulfilmentContext;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler\OrderInvoiceBatchDefinition;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler\OrderInvoiceBatchValidator;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\OrderInvoiceBatch;
use JardisSupport\Validation\ObjectValidator;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Handler\Node\MapOutcome;
use JardisSupport\Workflow\ProcessRunner;
use Throwable;

/**
 * Node RunBatch of the process BatchOrderFulfilment (call).
 *
 * Calls the sub-process OrderInvoiceBatch.
 */
final class RunBatch extends EcommerceContext
{
    /**
     * @node-id f1b2c302
     * @throws Throwable
     */
    public function __invoke(BatchOrderFulfilmentContext $ctx): NodeResult
    {
        $runner = ProcessRunner::forKernel(
            $this->resource(),
            new ObjectValidator(registry: (new OrderInvoiceBatchValidator())(), fieldPaths: true)
        );
        // Input mapping of the sub-process OrderInvoiceBatch: the developer's to complete.
        $outcome = $runner->run(
            (new OrderInvoiceBatchDefinition())(),
            new OrderInvoiceBatch(
                requestedBy: \call_user_func(
                    static fn(): mixed => throw new \RuntimeException('Sub-DTO-Feld requestedBy fuellen: RunBatch')
                ),
                primaryCommand: \call_user_func(
                    static fn(): mixed => throw new \RuntimeException('Sub-DTO-Feld primaryCommand fuellen: RunBatch')
                ),
                lineCommands: \call_user_func(
                    static fn(): mixed => throw new \RuntimeException('Sub-DTO-Feld lineCommands fuellen: RunBatch')
                )
            ),
            null,
            fn(string $class): object => $this->handle($class),
            $ctx
        );

        return (new MapOutcome())($outcome, null, 'OrderInvoiceBatch');
    }
}

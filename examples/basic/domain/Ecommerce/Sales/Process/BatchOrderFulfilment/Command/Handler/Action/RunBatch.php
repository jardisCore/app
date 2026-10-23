<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\BatchOrderFulfilment\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\BatchOrderFulfilment\Command\Handler\BatchOrderFulfilmentContext;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler\OrderInvoiceBatchHandler;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\OrderInvoiceBatch;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use JardisSupport\Workflow\Data\NodeResult;
use JardisSupport\Workflow\Handler\Node\MapResponse;
use Throwable;

/**
 * Node RunBatch of the process BatchOrderFulfilment (call).
 *
 * Calls the sub-process OrderInvoiceBatch.
 *
 * Kontext-Kette (von oben nach unten):
 * Domain: Ecommerce
 * BC: Sales
 * Prozess: BatchOrderFulfilment
 *     Caller-Fixture fuer R10: ruft OrderInvoiceBatch als Sub-Prozess auf.
 *     Belegt R4 (Einzel-Command-Feld primaryCommand: UpdateOrder|UpdateInvoice),
 *     R5 (Listen-Command-Feld lineCommands: array<AddOrderItem|AddInvoiceLine>)
 *     und R1 (Skalare requestedBy: string, note: string).
 * Knoten: RunBatch
 */
final class RunBatch extends EcommerceContext
{
    /**
     * @node-id f1b2c302
     * @throws Throwable
     */
    public function __invoke(BatchOrderFulfilmentContext $ctx): NodeResult
    {
        // Input mapping of the sub-process OrderInvoiceBatch: the developer's to complete.
        /** @var DomainResponseInterface $response */
        $response = $this->context(
            OrderInvoiceBatchHandler::class,
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
            depth: $ctx->depth() + 1
        )();

        return (new MapResponse())($response);
    }
}

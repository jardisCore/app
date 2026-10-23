<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler\OrderInvoiceBatchContext;
use JardisSupport\Workflow\Data\NodeResult;

/**
 * Node ApplyBatch of the process OrderInvoiceBatch (action).
 *
 * Verarbeitet die typisierten Commands. Die Fan-out-Dispatch-Schleife
 * (foreach … match(instanceof)) ist Dev-Code und lebt hier im logic(),
 * nicht im generierten DTO.
 *
 * Kontext-Kette (von oben nach unten):
 * Domain: Ecommerce
 * BC: Sales
 * Prozess: OrderInvoiceBatch
 *     Typisierter Batch-Eingang (R3): das Input-DTO komponiert Aggregat-Commands
 *     seines BC als Union — ein Einzel-Command-Feld und ein Listen-Command-Feld,
 *     beide cross-aggregat (Order + Invoice). Skalar-Felder gemischt; die
 *     Dispatch-Schleife bleibt Dev-Code im Knoten (R7), nicht Generat.
 * Knoten: ApplyBatch
 */
final class ApplyBatch extends EcommerceContext
{
    /**
     * @todo iteration-pending
     * @node-id b2c3d402
     */
    public function __invoke(OrderInvoiceBatchContext $ctx): NodeResult
    {
        throw new \RuntimeException(
            'Not implemented: write a mini-PRD or body for ' . self::class
        );
    }
}

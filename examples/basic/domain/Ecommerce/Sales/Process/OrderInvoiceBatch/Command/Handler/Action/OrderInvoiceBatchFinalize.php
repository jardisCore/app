<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler\OrderInvoiceBatchContext;
use JardisSupport\Workflow\Data\NodeResult;

/**
 * Node OrderInvoiceBatchFinalize of the process OrderInvoiceBatch (action).
 *
 * Platzhalter fuer den Output-Sub-Flow. Reduziert die gesammelten
 * Process-Ergebnisse zur Domain-Response.
 *
 * Kontext-Kette (von oben nach unten):
 * Domain: Ecommerce
 * BC: Sales
 * Prozess: OrderInvoiceBatch
 *     Typisierter Batch-Eingang (R3): das Input-DTO komponiert Aggregat-Commands
 *     seines BC als Union — ein Einzel-Command-Feld und ein Listen-Command-Feld,
 *     beide cross-aggregat (Order + Invoice). Skalar-Felder gemischt; die
 *     Dispatch-Schleife bleibt Dev-Code im Knoten (R7), nicht Generat.
 * Knoten: OrderInvoiceBatchFinalize
 */
final class OrderInvoiceBatchFinalize extends EcommerceContext
{
    /**
     * @node-id c3d4e503
     */
    public function __invoke(OrderInvoiceBatchContext $ctx): NodeResult
    {
        throw new \RuntimeException(
            'Not implemented: write a mini-PRD or body for ' . self::class
        );
    }
}

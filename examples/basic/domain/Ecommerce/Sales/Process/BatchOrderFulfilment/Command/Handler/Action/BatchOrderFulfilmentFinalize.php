<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\BatchOrderFulfilment\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\BatchOrderFulfilment\Command\Handler\BatchOrderFulfilmentContext;
use JardisSupport\Workflow\Data\NodeResult;

/**
 * Node BatchOrderFulfilmentFinalize of the process BatchOrderFulfilment (action).
 *
 * Platzhalter fuer den Output-Sub-Flow.
 * Reduziert die Ergebnisse zur Domain-Response.
 *
 * Mandant und Nutzer des Laufs: $ctx->input()->tenant, $ctx->input()->user (beide string).
 *
 * Kontext-Kette (von oben nach unten):
 * Domain: Ecommerce
 * BC: Sales
 * Prozess: BatchOrderFulfilment
 *     Caller-Fixture fuer R10: ruft OrderInvoiceBatch als Sub-Prozess auf.
 *     Belegt R4 (Einzel-Command-Feld primaryCommand: UpdateOrder|UpdateInvoice),
 *     R5 (Listen-Command-Feld lineCommands: array<AddOrderItem|AddInvoiceLine>)
 *     und R1 (Skalare requestedBy: string, note: string).
 * Knoten: BatchOrderFulfilmentFinalize
 */
final class BatchOrderFulfilmentFinalize extends EcommerceContext
{
    /**
     * @node-id f2c3d403
     */
    public function __invoke(BatchOrderFulfilmentContext $ctx): NodeResult
    {
        throw new \RuntimeException(
            'Not implemented: write a mini-PRD or body for ' . self::class
        );
    }
}

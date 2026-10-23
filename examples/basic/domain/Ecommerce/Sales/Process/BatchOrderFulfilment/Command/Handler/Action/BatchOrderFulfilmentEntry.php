<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\BatchOrderFulfilment\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\BatchOrderFulfilment\Command\Handler\BatchOrderFulfilmentContext;
use JardisSupport\Workflow\Data\NodeResult;

/**
 * Node BatchOrderFulfilmentEntry of the process BatchOrderFulfilment (action).
 *
 * Platzhalter fuer den Input-Sub-Flow.
 * Bereitet den Command-DTO-Strom auf.
 *
 * Kontext-Kette (von oben nach unten):
 * Domain: Ecommerce
 * BC: Sales
 * Prozess: BatchOrderFulfilment
 *     Caller-Fixture fuer R10: ruft OrderInvoiceBatch als Sub-Prozess auf.
 *     Belegt R4 (Einzel-Command-Feld primaryCommand: UpdateOrder|UpdateInvoice),
 *     R5 (Listen-Command-Feld lineCommands: array<AddOrderItem|AddInvoiceLine>)
 *     und R1 (Skalare requestedBy: string, note: string).
 * Knoten: BatchOrderFulfilmentEntry
 */
final class BatchOrderFulfilmentEntry extends EcommerceContext
{
    /**
     * @node-id f0a1b201
     */
    public function __invoke(BatchOrderFulfilmentContext $ctx): NodeResult
    {
        throw new \RuntimeException(
            'Not implemented: write a mini-PRD or body for ' . self::class
        );
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\OrderInvoiceBatch\Command\Handler\OrderInvoiceBatchContext;
use JardisSupport\Workflow\Data\NodeResult;

/**
 * Node OrderInvoiceBatchEntry of the process OrderInvoiceBatch (action).
 *
 * Platzhalter fuer den Input-Sub-Flow. Bereitet den Command-DTO-Strom
 * ($cmd->primaryCommand / $cmd->lineCommands) als iterierbaren Eingang auf.
 */
final class OrderInvoiceBatchEntry extends EcommerceContext
{
    /**
     * @node-id a1b2c301
     */
    public function __invoke(OrderInvoiceBatchContext $ctx): NodeResult
    {
        throw new \RuntimeException(
            'Not implemented: write a mini-PRD or body for ' . self::class
        );
    }
}

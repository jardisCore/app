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

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

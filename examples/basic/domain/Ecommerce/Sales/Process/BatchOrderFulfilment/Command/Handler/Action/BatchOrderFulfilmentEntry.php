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

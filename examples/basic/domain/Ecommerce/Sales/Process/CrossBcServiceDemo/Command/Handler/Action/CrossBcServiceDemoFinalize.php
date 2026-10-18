<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\CrossBcServiceDemo\Command\Handler\CrossBcServiceDemoContext;
use JardisSupport\Workflow\Data\NodeResult;

/**
 * Node CrossBcServiceDemoFinalize of the process CrossBcServiceDemo (action).
 *
 * Platzhalter-Abschluss (onFail-Zweig) — bleibt bewusst ein Wurf-Stub
 * (kein consumedCall) — KI-/Dev-Hoheit.
 */
final class CrossBcServiceDemoFinalize extends EcommerceContext
{
    /**
     * @node-id a3b4c503
     */
    public function __invoke(CrossBcServiceDemoContext $ctx): NodeResult
    {
        throw new \RuntimeException(
            'Not implemented: write a mini-PRD or body for ' . self::class
        );
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler\ExternalCallServiceDemoContext;
use JardisSupport\Workflow\Data\NodeResult;

/**
 * Node ExternalCallServiceDemoFinalize of the process ExternalCallServiceDemo (action).
 *
 * Platzhalter-Abschluss (onFail-Zweig) — bleibt bewusst ein Wurf-Stub
 * (kein consumedCall/externalCall) — KI-/Dev-Hoheit.
 */
final class ExternalCallServiceDemoFinalize extends EcommerceContext
{
    /**
     * @node-id e4d5e604
     */
    public function __invoke(ExternalCallServiceDemoContext $ctx): NodeResult
    {
        throw new \RuntimeException(
            'Not implemented: write a mini-PRD or body for ' . self::class
        );
    }
}

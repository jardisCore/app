<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\Handler\OrderInvoiceUnionAlertContext;
use JardisSupport\Workflow\Data\NodeResult;

/**
 * Node EvaluateSubjectRemoval of the process OrderInvoiceUnionAlert (action).
 *
 * Platzhalter fuer die Entfernungs-Pruefung.
 */
final class EvaluateSubjectRemoval extends EcommerceContext
{
    /**
     * @node-id v1a2b301
     */
    public function __invoke(OrderInvoiceUnionAlertContext $ctx): NodeResult
    {
        throw new \RuntimeException(
            'Not implemented: write a mini-PRD or body for ' . self::class
        );
    }
}

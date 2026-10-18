<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderInvoiceUnionAlert\Command\Handler;

use JardisSupport\Validation\ValidatorRegistry;

/**
 * Input gate of the process OrderInvoiceUnionAlert: the validator registry of its input class graph.
 *
 * Hermetic - rules come from the types and value lists of the input fields.
 */
final class OrderInvoiceUnionAlertValidator
{
    public function __invoke(): ValidatorRegistry
    {
        return new ValidatorRegistry();
    }
}

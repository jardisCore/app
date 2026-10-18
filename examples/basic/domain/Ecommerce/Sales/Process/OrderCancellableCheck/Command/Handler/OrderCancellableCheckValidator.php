<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderCancellableCheck\Command\Handler;

use JardisSupport\Validation\ValidatorRegistry;

/**
 * Input gate of the process OrderCancellableCheck: the validator registry of its input class graph.
 *
 * Hermetic - rules come from the types and value lists of the input fields.
 */
final class OrderCancellableCheckValidator
{
    public function __invoke(): ValidatorRegistry
    {
        return new ValidatorRegistry();
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderLimitCheck\Command\Handler;

use Ecommerce\Sales\Process\OrderLimitCheck\Command\OrderLimitCheck;
use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\ValidatorRegistry;
use JardisSupport\Validation\Validator\Format;

/**
 * Input gate of the process OrderLimitCheck: the validator registry of its input class graph.
 *
 * Hermetic - rules come from the types and value lists of the input fields.
 */
final class OrderLimitCheckValidator
{
    public function __invoke(): ValidatorRegistry
    {
        $registry = new ValidatorRegistry();
        $validator = new CompositeFieldValidator();
        $validator->field('limit')
            ->validates(Format::class, Format::pattern('/^-?\d+(\.\d+)?$/'));
        $registry->register(OrderLimitCheck::class, $validator);

        return $registry;
    }
}

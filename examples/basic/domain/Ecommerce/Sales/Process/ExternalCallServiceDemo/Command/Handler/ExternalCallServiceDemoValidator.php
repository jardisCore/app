<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\Handler;

use Ecommerce\Sales\Process\ExternalCallServiceDemo\Command\ExternalCallServiceDemo;
use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\ValidatorRegistry;
use JardisSupport\Validation\Validator\Contain;
use JardisSupport\Validation\Validator\DateTime;
use JardisSupport\Validation\Validator\Format;
use JardisSupport\Validation\Validator\Uuid;

/**
 * Input gate of the process ExternalCallServiceDemo: the validator registry of its input class graph.
 *
 * Hermetic - rules come from the types and value lists of the input fields.
 */
final class ExternalCallServiceDemoValidator
{
    public function __invoke(): ValidatorRegistry
    {
        $registry = new ValidatorRegistry();
        $validator = new CompositeFieldValidator();
        $validator->field('confirmationKey')
            ->validates(Uuid::class);
        $validator->field('requestedOn')
            ->validates(DateTime::class, DateTime::dateOnly());
        $validator->field('budget')
            ->validates(Format::class, Format::pattern('/^-?\d+(\.\d+)?$/'));
        $validator->field('channel')
            ->validates(Contain::class, Contain::oneOf(['email', 'post', 'portal']));
        $registry->register(ExternalCallServiceDemo::class, $validator);

        return $registry;
    }
}

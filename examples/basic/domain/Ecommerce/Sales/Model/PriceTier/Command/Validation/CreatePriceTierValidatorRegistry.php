<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Command\Validation;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\PriceTier\Command\PriceTier;
use Ecommerce\Sales\Model\PriceTier\Command\Validation\PriceTierValidator;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;

/**
 * Validator registry of the CommandPriceTier command DTO graph.
 *
 * One validator per DTO class of the graph (root + nested ONE + nested MANY); the
 * door's input check validates the whole tree with it.
 */
class CreatePriceTierValidatorRegistry extends EcommerceContext
{
    /**
     * Builds the registry.
     *
     * @return ValidatorRegistry
     * @throws Throwable
     */
    public function __invoke(): ValidatorRegistry
    {
        $registry = new ValidatorRegistry();
        $registry->register(PriceTier::class, ($this->handle(PriceTierValidator::class))());

        return $registry;
    }
}

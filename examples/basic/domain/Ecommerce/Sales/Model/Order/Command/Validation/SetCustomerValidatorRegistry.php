<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command\Validation;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Command\Address;
use Ecommerce\Sales\Model\Order\Command\SetCustomer;
use Ecommerce\Sales\Model\Order\Command\Validation\AddressValidator;
use Ecommerce\Sales\Model\Order\Command\Validation\CustomerValidator;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;

/**
 * Validator registry of the CommandSetCustomer command DTO graph.
 *
 * One validator per DTO class of the graph (root + nested ONE + nested MANY); the
 * door's input check validates the whole tree with it.
 */
class SetCustomerValidatorRegistry extends EcommerceContext
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
        $registry->register(SetCustomer::class, ($this->handle(CustomerValidator::class))());
        $registry->register(Address::class, ($this->handle(AddressValidator::class))());

        return $registry;
    }
}

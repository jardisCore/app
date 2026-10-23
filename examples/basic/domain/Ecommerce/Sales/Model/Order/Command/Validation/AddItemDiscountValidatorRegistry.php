<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command\Validation;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Command\AddItemDiscount;
use Ecommerce\Sales\Model\Order\Command\Validation\ItemDiscountValidator;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;

/**
 * Validator registry of the CommandAddItemDiscount command DTO graph.
 *
 * One validator per DTO class of the graph (root + nested ONE + nested MANY); the
 * door's input check validates the whole tree with it.
 */
class AddItemDiscountValidatorRegistry extends EcommerceContext
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
        $registry->register(AddItemDiscount::class, ($this->handle(ItemDiscountValidator::class))());

        return $registry;
    }
}

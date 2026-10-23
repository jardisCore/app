<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command\Validation;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Command\AddOrderItem;
use Ecommerce\Sales\Model\Order\Command\ItemDiscount;
use Ecommerce\Sales\Model\Order\Command\Validation\ItemDiscountValidator;
use Ecommerce\Sales\Model\Order\Command\Validation\OrderItemValidator;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;

/**
 * Validator registry of the CommandAddOrderItem command DTO graph.
 *
 * One validator per DTO class of the graph (root + nested ONE + nested MANY); the
 * door's input check validates the whole tree with it.
 */
class AddOrderItemValidatorRegistry extends EcommerceContext
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
        $registry->register(AddOrderItem::class, ($this->handle(OrderItemValidator::class))());
        $registry->register(ItemDiscount::class, ($this->handle(ItemDiscountValidator::class))());

        return $registry;
    }
}

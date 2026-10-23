<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Command\Validation;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Command\Address;
use Ecommerce\Sales\Model\Order\Command\Customer;
use Ecommerce\Sales\Model\Order\Command\ItemDiscount;
use Ecommerce\Sales\Model\Order\Command\Order;
use Ecommerce\Sales\Model\Order\Command\OrderItem;
use Ecommerce\Sales\Model\Order\Command\Validation\AddressValidator;
use Ecommerce\Sales\Model\Order\Command\Validation\CustomerValidator;
use Ecommerce\Sales\Model\Order\Command\Validation\ItemDiscountValidator;
use Ecommerce\Sales\Model\Order\Command\Validation\OrderItemValidator;
use Ecommerce\Sales\Model\Order\Command\Validation\OrderValidator;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;

/**
 * Validator registry of the CommandOrder command DTO graph.
 *
 * One validator per DTO class of the graph (root + nested ONE + nested MANY); the
 * door's input check validates the whole tree with it.
 */
class CreateOrderValidatorRegistry extends EcommerceContext
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
        $registry->register(Order::class, ($this->handle(OrderValidator::class))());
        $registry->register(Customer::class, ($this->handle(CustomerValidator::class))());
        $registry->register(Address::class, ($this->handle(AddressValidator::class))());
        $registry->register(OrderItem::class, ($this->handle(OrderItemValidator::class))());
        $registry->register(ItemDiscount::class, ($this->handle(ItemDiscountValidator::class))());

        return $registry;
    }
}

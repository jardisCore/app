<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\RuleGuardedOrderIntake\Command\Handler;

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

/**
 * Input gate of the process RuleGuardedOrderIntake: the validator registry of its input class graph.
 *
 * Hermetic - rules come from the types and value lists of the input fields.
 */
final class RuleGuardedOrderIntakeValidator
{
    public function __invoke(): ValidatorRegistry
    {
        $registry = new ValidatorRegistry();
        $registry->register(Order::class, (new OrderValidator())());
        $registry->register(Customer::class, (new CustomerValidator())());
        $registry->register(Address::class, (new AddressValidator())());
        $registry->register(OrderItem::class, (new OrderItemValidator())());
        $registry->register(ItemDiscount::class, (new ItemDiscountValidator())());

        return $registry;
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Process\OrderLimitAfterPlace\Command\Handler;

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
use Ecommerce\Sales\Process\OrderLimitAfterPlace\Command\OrderLimitAfterPlace;
use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\ValidatorRegistry;
use JardisSupport\Validation\Validator\Format;

/**
 * Input gate of the process OrderLimitAfterPlace: the validator registry of its input class graph.
 *
 * Hermetic - rules come from the types and value lists of the input fields.
 */
final class OrderLimitAfterPlaceValidator
{
    public function __invoke(): ValidatorRegistry
    {
        $registry = new ValidatorRegistry();
        $validator = new CompositeFieldValidator();
        $validator->field('limit')
            ->validates(Format::class, Format::pattern('/^-?\d+(\.\d+)?$/'));
        $registry->register(OrderLimitAfterPlace::class, $validator);
        $registry->register(Order::class, (new OrderValidator())());
        $registry->register(Customer::class, (new CustomerValidator())());
        $registry->register(Address::class, (new AddressValidator())());
        $registry->register(OrderItem::class, (new OrderItemValidator())());
        $registry->register(ItemDiscount::class, (new ItemDiscountValidator())());

        return $registry;
    }
}

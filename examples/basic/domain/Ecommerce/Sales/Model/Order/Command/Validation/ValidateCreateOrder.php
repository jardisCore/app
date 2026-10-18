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
use JardisSupport\Validation\ObjectValidator;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;
use Ecommerce\Sales\Model\Order\Command\Order as CommandOrder;

/**
 * Validates the CommandOrder command DTO graph.
 *
 * Sets up ObjectValidator with ValidatorRegistry for recursive validation
 * of the complete DTO tree (root + nested ONE + nested MANY).
 */
class ValidateCreateOrder extends EcommerceContext
{
    /**
     * Validates the command DTO.
     *
     * @param CommandOrder $dto Command DTO
     * @throws ValidationException If validation fails
     * @throws Throwable
     */
    public function __invoke(CommandOrder $dto): void
    {
        $registry = new ValidatorRegistry();
        $registry->register(Order::class, ($this->handle(OrderValidator::class))());
        $registry->register(Customer::class, ($this->handle(CustomerValidator::class))());
        $registry->register(Address::class, ($this->handle(AddressValidator::class))());
        $registry->register(OrderItem::class, ($this->handle(OrderItemValidator::class))());
        $registry->register(ItemDiscount::class, ($this->handle(ItemDiscountValidator::class))());

        $validator = new ObjectValidator($registry, indexedLists: true);
        $result = $validator->validate($dto);
        $errors = $this->filterEmptyErrors($result->getErrors());
        $kinds = $this->filterEmptyErrors($result->getKinds());

        if (!empty($errors)) {
            throw new ValidationException($errors, $kinds);
        }
    }

    /**
     * Recursively filters empty error arrays from validation results.
     *
     * @param array<string, mixed> $errors
     * @return array<string, mixed>
     */
    private function filterEmptyErrors(array $errors): array
    {
        $filtered = [];

        foreach ($errors as $key => $value) {
            if (is_array($value)) {
                $nested = $this->filterEmptyErrors($value);
                if (!empty($nested)) {
                    $filtered[$key] = $nested;
                }
            } else {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }
}

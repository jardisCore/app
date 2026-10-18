<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Entity\Validation\AddressValidator;
use Ecommerce\Sales\Entity\Validation\CustomerValidator;
use Ecommerce\Sales\Entity\Validation\ItemDiscountValidator;
use Ecommerce\Sales\Entity\Validation\OrderItemValidator;
use Ecommerce\Sales\Entity\Validation\OrderValidator;
use Ecommerce\Sales\Model\Order\Command\Validation\ValidationException;
use JardisSupport\Validation\CompositeFieldValidator;
use Throwable;
use Ecommerce\Sales\Model\Order\Aggregate\Order as OrderHandler;

/**
 * Validates Order aggregate entities before persistence.
 *
 * Validates all entities using their generated validators.
 * Called automatically by Repository before PersistAggregate.
 */
class ValidateOrder extends EcommerceContext
{
    /**
     * Validates all entities in the aggregate.
     *
     * @param OrderHandler $handler Aggregate handler
     * @throws ValidationException If validation fails
     * @throws Throwable
     */
    public function __invoke(OrderHandler $handler): void
    {
        if ($handler->isMarkedForDeletion()) {
            return;
        }

        $errors = [];
        $kinds = [];
        $aggregate = $handler->getData();

        if (!empty($handler->getEntityData('order')['values'])) {
            $this->validateEntity($aggregate, OrderValidator::class, 'order', $errors, $kinds);
        }

        $customer = $aggregate->getCustomer();
        if ($customer !== null && !empty($handler->getEntityData('customer')['values'])) {
            $this->validateEntity($customer, CustomerValidator::class, 'customer', $errors, $kinds);
        }
        if ($customer !== null) {
            $address = $customer->getAddress();
            if ($address !== null && !empty($handler->getEntityData('address')['values'])) {
                $this->validateEntity($address, AddressValidator::class, 'address', $errors, $kinds);
            }
        }
        $orderItemCollection = $handler->getCollectionData('orderItem');
        foreach ($aggregate->getOrderItem() as $index => $orderItem) {
            if (!empty($orderItemCollection[$index]['values'])) {
                $this->validateEntity($orderItem, OrderItemValidator::class, "orderItem[{$index}]", $errors, $kinds);
            }
        }
        $itemDiscountCollection = $handler->getCollectionData('itemDiscount');
        $itemDiscountPosition = 0;
        foreach ($aggregate->getOrderItem() as $orderItemIndex => $orderItem) {
            foreach ($orderItem->getItemDiscount() as $itemDiscountIndex => $itemDiscount) {
                if (!empty($itemDiscountCollection[$itemDiscountPosition]['values'])) {
                    $this->validateEntity(
                        $itemDiscount,
                        ItemDiscountValidator::class,
                        "orderItem[{$orderItemIndex}].itemDiscount[{$itemDiscountIndex}]",
                        $errors,
                        $kinds
                    );
                }
                $itemDiscountPosition++;
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors, $kinds, root: 'order');
        }
    }

    /**
     * Validates a single entity against its validator.
     *
     * @param object $entity The entity to validate
     * @param class-string $validatorClass Validator class name
     * @param string $entityName Entity identifier for error grouping
     * @param array<string, mixed> $errors Collected errors (by reference)
     * @param array<string, mixed> $kinds Collected validator kinds, same shape (by reference)
     * @throws Throwable
     */
    protected function validateEntity(
        object $entity,
        string $validatorClass,
        string $entityName,
        array &$errors,
        array &$kinds
    ): void {
        $validatorInstance = $this->handle($validatorClass);
        /** @var callable(): CompositeFieldValidator $validatorInstance */
        $validator = $validatorInstance();
        $result = $validator->validate($entity);

        if (!$result->isValid()) {
            $errors[$entityName] = $result->getErrors();
            $kinds[$entityName] = $result->getKinds();
        }
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Order\Command\Validation\ValidationException;
use Ecommerce\Sales\Entity\Validation\AddressValidator;
use Ecommerce\Sales\Entity\Validation\CustomerValidator;
use Ecommerce\Sales\Entity\Validation\ItemDiscountValidator;
use Ecommerce\Sales\Entity\Validation\OrderItemValidator;
use Ecommerce\Sales\Entity\Validation\OrderValidator;
use JsonException;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Order as OrderHandler;

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
     * @throws JsonException
     * @throws Throwable
     */
    public function __invoke(OrderHandler $handler): void
    {
        if ($handler->isMarkedForDeletion()) {
            return;
        }

        $errors = [];
        $aggregate = $handler->getData();

        if (!empty($handler->getEntityData('order')['values'])) {
            $this->validateEntity($aggregate, OrderValidator::class, 'order', $errors);
        }

        $customer = $aggregate->getCustomer();
        if ($customer !== null && !empty($handler->getEntityData('customer')['values'])) {
            $this->validateEntity($customer, CustomerValidator::class, 'customer', $errors);
        }
        if ($customer !== null) {
            $address = $customer->getAddress();
            if ($address !== null && !empty($handler->getEntityData('address')['values'])) {
                $this->validateEntity($address, AddressValidator::class, 'address', $errors);
            }
        }
        $orderItemCollection = $handler->getCollectionData('orderItem');
        foreach ($aggregate->getOrderItem() as $index => $orderItem) {
            if (!empty($orderItemCollection[$index]['values'])) {
                $this->validateEntity($orderItem, OrderItemValidator::class, "orderItem[{$index}]", $errors);
            }
        }
        $itemDiscountCollection = $handler->getCollectionData('itemDiscount');
        foreach (array_merge([], ...array_map(fn(object $e) => $e->getItemDiscount(), $aggregate->getOrderItem())) as $index => $itemDiscount) {
            if (!empty($itemDiscountCollection[$index]['values'])) {
                $this->validateEntity($itemDiscount, ItemDiscountValidator::class, "itemDiscount[{$index}]", $errors);
            }
        }

        if (!empty($errors)) {
            throw new ValidationException(json_encode($errors, JSON_THROW_ON_ERROR));
        }
    }

    /**
     * Validates a single entity against its validator.
     *
     * @param object $entity The entity to validate
     * @param class-string $validatorClass Validator class name
     * @param string $entityName Entity identifier for error grouping
     * @param array<string, mixed> $errors Collected errors (by reference)
     * @throws Throwable
     */
    protected function validateEntity(
        object $entity,
        string $validatorClass,
        string $entityName,
        array &$errors
    ): void {
        $validator = ($this->handle($validatorClass))();
        $result = $validator->validate($entity);

        if (!$result->isValid()) {
            $errors[$entityName] = $result->getErrors();
        }
    }
}

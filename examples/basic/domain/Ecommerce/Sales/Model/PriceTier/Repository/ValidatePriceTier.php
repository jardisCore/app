<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Entity\Validation\PriceTierValidator;
use Ecommerce\Sales\Model\PriceTier\Command\Validation\ValidationException;
use JardisSupport\Validation\CompositeFieldValidator;
use Throwable;
use Ecommerce\Sales\Model\PriceTier\Aggregate\PriceTier as PriceTierHandler;

/**
 * Validates PriceTier aggregate entities before persistence.
 *
 * Validates all entities using their generated validators.
 * Called automatically by Repository before PersistAggregate.
 */
class ValidatePriceTier extends EcommerceContext
{
    /**
     * Validates all entities in the aggregate.
     *
     * @param PriceTierHandler $handler Aggregate handler
     * @throws ValidationException If validation fails
     * @throws Throwable
     */
    public function __invoke(PriceTierHandler $handler): void
    {
        if ($handler->isMarkedForDeletion()) {
            return;
        }

        $errors = [];
        $kinds = [];
        $aggregate = $handler->getData();

        if (!empty($handler->getEntityData('priceTier')['values'])) {
            $this->validateEntity($aggregate, PriceTierValidator::class, 'priceTier', $errors, $kinds);
        }

        if (!empty($errors)) {
            throw new ValidationException($errors, $kinds, root: 'priceTier');
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

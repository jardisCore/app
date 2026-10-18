<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Repository;

use Ecommerce\Catalog\Entity\Validation\CategoryTranslationValidator;
use Ecommerce\Catalog\Entity\Validation\CategoryValidator;
use Ecommerce\Catalog\Model\Category\Command\Validation\ValidationException;
use Ecommerce\EcommerceContext;
use JardisSupport\Validation\CompositeFieldValidator;
use Throwable;
use Ecommerce\Catalog\Model\Category\Aggregate\Category as CategoryHandler;

/**
 * Validates Category aggregate entities before persistence.
 *
 * Validates all entities using their generated validators.
 * Called automatically by Repository before PersistAggregate.
 */
class ValidateCategory extends EcommerceContext
{
    /**
     * Validates all entities in the aggregate.
     *
     * @param CategoryHandler $handler Aggregate handler
     * @throws ValidationException If validation fails
     * @throws Throwable
     */
    public function __invoke(CategoryHandler $handler): void
    {
        if ($handler->isMarkedForDeletion()) {
            return;
        }

        $errors = [];
        $kinds = [];
        $aggregate = $handler->getData();

        if (!empty($handler->getEntityData('category')['values'])) {
            $this->validateEntity($aggregate, CategoryValidator::class, 'category', $errors, $kinds);
        }

        $categoryTranslationCollection = $handler->getCollectionData('categoryTranslation');
        foreach ($aggregate->getCategoryTranslation() as $index => $categoryTranslation) {
            if (!empty($categoryTranslationCollection[$index]['values'])) {
                $this->validateEntity(
                    $categoryTranslation,
                    CategoryTranslationValidator::class,
                    "categoryTranslation[{$index}]",
                    $errors,
                    $kinds
                );
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors, $kinds, root: 'category');
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

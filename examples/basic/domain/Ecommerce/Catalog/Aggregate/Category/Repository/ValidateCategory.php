<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Repository;

use Ecommerce\Catalog\Aggregate\Category\Command\Validation\ValidationException;
use Ecommerce\Catalog\Entity\Validation\CategoryTranslationValidator;
use Ecommerce\Catalog\Entity\Validation\CategoryValidator;
use Ecommerce\EcommerceContext;
use JsonException;
use Throwable;
use Ecommerce\Catalog\Aggregate\Category\Aggregate\Category as CategoryHandler;

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
     * @throws JsonException
     * @throws Throwable
     */
    public function __invoke(CategoryHandler $handler): void
    {
        if ($handler->isMarkedForDeletion()) {
            return;
        }

        $errors = [];
        $aggregate = $handler->getData();

        if (!empty($handler->getEntityData('category')['values'])) {
            $this->validateEntity($aggregate, CategoryValidator::class, 'category', $errors);
        }

        $categoryTranslationCollection = $handler->getCollectionData('categoryTranslation');
        foreach ($aggregate->getCategoryTranslation() as $index => $categoryTranslation) {
            if (!empty($categoryTranslationCollection[$index]['values'])) {
                $this->validateEntity(
                    $categoryTranslation,
                    CategoryTranslationValidator::class,
                    "categoryTranslation[{$index}]",
                    $errors
                );
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

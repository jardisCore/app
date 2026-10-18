<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Command\Validation;

use Ecommerce\Catalog\Model\Category\Command\Category;
use Ecommerce\Catalog\Model\Category\Command\CategoryTranslation;
use Ecommerce\Catalog\Model\Category\Command\Validation\CategoryTranslationValidator;
use Ecommerce\Catalog\Model\Category\Command\Validation\CategoryValidator;
use Ecommerce\EcommerceContext;
use JardisSupport\Validation\ObjectValidator;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;
use Ecommerce\Catalog\Model\Category\Command\Category as CommandCategory;

/**
 * Validates the CommandCategory command DTO graph.
 *
 * Sets up ObjectValidator with ValidatorRegistry for recursive validation
 * of the complete DTO tree (root + nested ONE + nested MANY).
 */
class ValidateCreateCategory extends EcommerceContext
{
    /**
     * Validates the command DTO.
     *
     * @param CommandCategory $dto Command DTO
     * @throws ValidationException If validation fails
     * @throws Throwable
     */
    public function __invoke(CommandCategory $dto): void
    {
        $registry = new ValidatorRegistry();
        $registry->register(Category::class, ($this->handle(CategoryValidator::class))());
        $registry->register(CategoryTranslation::class, ($this->handle(CategoryTranslationValidator::class))());

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

<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Command\Validation;

use Ecommerce\Catalog\Model\Category\Command\UpdateCategoryTranslation;
use Ecommerce\Catalog\Model\Category\Command\Validation\CategoryTranslationValidator;
use Ecommerce\EcommerceContext;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;

/**
 * Validator registry of the CommandUpdateCategoryTranslation command DTO graph.
 *
 * One validator per DTO class of the graph (root + nested ONE + nested MANY); the
 * door's input check validates the whole tree with it.
 */
class UpdateCategoryTranslationValidatorRegistry extends EcommerceContext
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
        $registry->register(UpdateCategoryTranslation::class, ($this->handle(CategoryTranslationValidator::class))());

        return $registry;
    }
}

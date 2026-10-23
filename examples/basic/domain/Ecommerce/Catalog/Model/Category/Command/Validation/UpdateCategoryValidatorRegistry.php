<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Command\Validation;

use Ecommerce\Catalog\Model\Category\Command\UpdateCategory;
use Ecommerce\Catalog\Model\Category\Command\Validation\CategoryValidator;
use Ecommerce\EcommerceContext;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;

/**
 * Validator registry of the CommandUpdateCategory command DTO graph.
 *
 * One validator per DTO class of the graph (root + nested ONE + nested MANY); the
 * door's input check validates the whole tree with it.
 */
class UpdateCategoryValidatorRegistry extends EcommerceContext
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
        $registry->register(UpdateCategory::class, ($this->handle(CategoryValidator::class))());

        return $registry;
    }
}

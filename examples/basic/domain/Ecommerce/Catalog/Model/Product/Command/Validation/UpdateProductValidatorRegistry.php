<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Validation;

use Ecommerce\Catalog\Model\Product\Command\UpdateProduct;
use Ecommerce\Catalog\Model\Product\Command\Validation\ProductValidator;
use Ecommerce\EcommerceContext;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;

/**
 * Validator registry of the CommandUpdateProduct command DTO graph.
 *
 * One validator per DTO class of the graph (root + nested ONE + nested MANY); the
 * door's input check validates the whole tree with it.
 */
class UpdateProductValidatorRegistry extends EcommerceContext
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
        $registry->register(UpdateProduct::class, ($this->handle(ProductValidator::class))());

        return $registry;
    }
}

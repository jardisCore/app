<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Validation;

use Ecommerce\Catalog\Model\Product\Command\AddProductImage;
use Ecommerce\Catalog\Model\Product\Command\Validation\ProductImageValidator;
use Ecommerce\EcommerceContext;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;

/**
 * Validator registry of the CommandAddProductImage command DTO graph.
 *
 * One validator per DTO class of the graph (root + nested ONE + nested MANY); the
 * door's input check validates the whole tree with it.
 */
class AddProductImageValidatorRegistry extends EcommerceContext
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
        $registry->register(AddProductImage::class, ($this->handle(ProductImageValidator::class))());

        return $registry;
    }
}

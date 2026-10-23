<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Validation;

use Ecommerce\Catalog\Model\Product\Command\UpdateProductVariant;
use Ecommerce\Catalog\Model\Product\Command\Validation\ProductVariantValidator;
use Ecommerce\EcommerceContext;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;

/**
 * Validator registry of the CommandUpdateProductVariant command DTO graph.
 *
 * One validator per DTO class of the graph (root + nested ONE + nested MANY); the
 * door's input check validates the whole tree with it.
 */
class UpdateProductVariantValidatorRegistry extends EcommerceContext
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
        $registry->register(UpdateProductVariant::class, ($this->handle(ProductVariantValidator::class))());

        return $registry;
    }
}

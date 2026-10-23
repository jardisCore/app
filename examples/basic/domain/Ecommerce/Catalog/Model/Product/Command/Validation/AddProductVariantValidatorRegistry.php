<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Validation;

use Ecommerce\Catalog\Model\Product\Command\AddProductVariant;
use Ecommerce\Catalog\Model\Product\Command\ProductVariantPrice;
use Ecommerce\Catalog\Model\Product\Command\Validation\ProductVariantPriceValidator;
use Ecommerce\Catalog\Model\Product\Command\Validation\ProductVariantValidator;
use Ecommerce\EcommerceContext;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;

/**
 * Validator registry of the CommandAddProductVariant command DTO graph.
 *
 * One validator per DTO class of the graph (root + nested ONE + nested MANY); the
 * door's input check validates the whole tree with it.
 */
class AddProductVariantValidatorRegistry extends EcommerceContext
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
        $registry->register(AddProductVariant::class, ($this->handle(ProductVariantValidator::class))());
        $registry->register(ProductVariantPrice::class, ($this->handle(ProductVariantPriceValidator::class))());

        return $registry;
    }
}

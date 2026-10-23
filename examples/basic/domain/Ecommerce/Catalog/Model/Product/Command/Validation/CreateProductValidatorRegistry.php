<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Validation;

use Ecommerce\Catalog\Model\Product\Command\Product;
use Ecommerce\Catalog\Model\Product\Command\ProductImage;
use Ecommerce\Catalog\Model\Product\Command\ProductVariant;
use Ecommerce\Catalog\Model\Product\Command\ProductVariantPrice;
use Ecommerce\Catalog\Model\Product\Command\Validation\ProductImageValidator;
use Ecommerce\Catalog\Model\Product\Command\Validation\ProductValidator;
use Ecommerce\Catalog\Model\Product\Command\Validation\ProductVariantPriceValidator;
use Ecommerce\Catalog\Model\Product\Command\Validation\ProductVariantValidator;
use Ecommerce\EcommerceContext;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;

/**
 * Validator registry of the CommandProduct command DTO graph.
 *
 * One validator per DTO class of the graph (root + nested ONE + nested MANY); the
 * door's input check validates the whole tree with it.
 */
class CreateProductValidatorRegistry extends EcommerceContext
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
        $registry->register(Product::class, ($this->handle(ProductValidator::class))());
        $registry->register(ProductImage::class, ($this->handle(ProductImageValidator::class))());
        $registry->register(ProductVariant::class, ($this->handle(ProductVariantValidator::class))());
        $registry->register(ProductVariantPrice::class, ($this->handle(ProductVariantPriceValidator::class))());

        return $registry;
    }
}

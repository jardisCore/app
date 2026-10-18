<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Process\UpdateProductInCatalog\Command\Handler;

use Ecommerce\Catalog\Model\Product\Command\UpdateProduct;
use Ecommerce\Catalog\Model\Product\Command\Validation\ProductValidator;
use JardisSupport\Validation\ValidatorRegistry;

/**
 * Input gate of the process UpdateProductInCatalog: the validator registry of its input class graph.
 *
 * Hermetic - rules come from the types and value lists of the input fields.
 */
final class UpdateProductInCatalogValidator
{
    public function __invoke(): ValidatorRegistry
    {
        $registry = new ValidatorRegistry();
        $registry->register(UpdateProduct::class, (new ProductValidator())());

        return $registry;
    }
}

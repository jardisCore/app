<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Command\Handler\Action;

use Ecommerce\Catalog\FieldMap;
use Ecommerce\EcommerceContext;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Catalog\Aggregate\Product\Command\UpdateProduct as CommandUpdateProduct;

/**
 * Action: BuildUpdateProductData
 */
class BuildUpdateProductData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandUpdateProduct $updateProduct Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandUpdateProduct $updateProduct): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($updateProduct);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["productIdentifier"], true),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->productsColumns());
    }
}

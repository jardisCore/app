<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Command\Handler\Action;

use Ecommerce\Catalog\FieldMap;
use Ecommerce\EcommerceContext;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Catalog\Aggregate\Product\Command\AddProductVariant as CommandAddProductVariant;

/**
 * Action: BuildAddProductVariantData
 */
class BuildAddProductVariantData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandAddProductVariant $addProductVariant Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandAddProductVariant $addProductVariant): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($addProductVariant);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["productIdentifier"], true),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->productVariantsColumns());
    }
}

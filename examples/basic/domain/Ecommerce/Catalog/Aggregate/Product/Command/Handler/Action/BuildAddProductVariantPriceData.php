<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Command\Handler\Action;

use Ecommerce\Catalog\FieldMap;
use Ecommerce\EcommerceContext;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Catalog\Aggregate\Product\Command\AddProductVariantPrice as CommandAddProductVariantPrice;

/**
 * Action: BuildAddProductVariantPriceData
 */
class BuildAddProductVariantPriceData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandAddProductVariantPrice $addProductVariantPrice Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandAddProductVariantPrice $addProductVariantPrice): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($addProductVariantPrice);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["productIdentifier","productVariantIdentifier"], true),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->productVariantPricesColumns());
    }
}

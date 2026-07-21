<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Command\Handler\Action;

use Ecommerce\Catalog\FieldMap;
use Ecommerce\EcommerceContext;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Catalog\Aggregate\Product\Command\AddProductImage as CommandAddProductImage;

/**
 * Action: BuildAddProductImageData
 */
class BuildAddProductImageData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandAddProductImage $addProductImage Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandAddProductImage $addProductImage): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($addProductImage);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["productIdentifier"], true),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->productImagesColumns());
    }
}

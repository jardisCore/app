<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Handler\Action;

use Ecommerce\Catalog\FieldMap;
use Ecommerce\EcommerceContext;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Catalog\Model\Product\Command\UpdateProductImage as CommandUpdateProductImage;

/**
 * Action: BuildUpdateProductImageData
 */
class BuildUpdateProductImageData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandUpdateProductImage $updateProductImage Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandUpdateProductImage $updateProductImage): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($updateProductImage);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["tenant","productIdentifier","productImageId"], true),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->productImagesColumns());
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Handler\Action;

use Ecommerce\Catalog\FieldMap;
use Ecommerce\EcommerceContext;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Catalog\Model\Product\Command\UpdateProductVariant as CommandUpdateProductVariant;

/**
 * Action: BuildUpdateProductVariantData
 */
class BuildUpdateProductVariantData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandUpdateProductVariant $updateProductVariant Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandUpdateProductVariant $updateProductVariant): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($updateProductVariant);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array($key, ["productIdentifier","productVariantIdentifier"], true),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->productVariantsColumns());
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Command\Handler\Action;

use Ecommerce\Catalog\FieldMap;
use Ecommerce\EcommerceContext;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Catalog\Model\Product\Command\UpdateProductVariantPrice as CommandUpdateProductVariantPrice;

/**
 * Action: BuildUpdateProductVariantPriceData
 */
class BuildUpdateProductVariantPriceData extends EcommerceContext
{
    /**
     * Builds the data array for entity hydration.
     *
     * @param CommandUpdateProductVariantPrice $updateProductVariantPrice Command data
     * @return array<string, mixed> Entity data
     * @throws Throwable
     */
    public function __invoke(CommandUpdateProductVariantPrice $updateProductVariantPrice): array
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($updateProductVariantPrice);

        return $this->handle(FieldMapper::class)->toColumns([
            ...array_filter(
                $rawData,
                fn($key) => !in_array(
                    $key,
                    ["productIdentifier","productVariantIdentifier","productVariantPriceId"],
                    true
                ),
                ARRAY_FILTER_USE_KEY
            ),
        ], $fieldMap->productVariantPricesColumns());
    }
}

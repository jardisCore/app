<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Command\Handler\Action;

use Ecommerce\Catalog\Aggregate\Product\Aggregate\Product;
use Ecommerce\Catalog\FieldMap;
use Ecommerce\EcommerceContext;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Catalog\Aggregate\Product\Command\Product as CommandProduct;

/**
 * Action: HydrateCreateProductEntities
 */
class HydrateCreateProductEntities extends EcommerceContext
{
    /**
     * Hydrates all entities from the command DTO.
     *
     * @param Product $handler Aggregate handler
     * @param CommandProduct $product Command data
     * @throws Throwable
     */
    public function __invoke(Product $handler, CommandProduct $product): void
    {
        $this->hydrateProduct($handler, $product);
        $this->hydrateProductImage($handler, $product);
        $this->hydrateProductVariant($handler, $product);
    }

    /**
     * Hydrates Product entity data.
     * @throws Throwable
     */
    protected function hydrateProduct(Product $handler, CommandProduct $product): void
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($product);

        $handler->setProduct($this->handle(FieldMapper::class)->toColumns(array_filter(
            $rawData,
            fn($key) => !in_array($key, ["productImage","productVariant"], true),
            ARRAY_FILTER_USE_KEY
        ), $fieldMap->productsColumns()));
    }

    /**
     * Hydrates ProductImage collection.
     * @throws Throwable
     */
    protected function hydrateProductImage(Product $handler, CommandProduct $product): void
    {
        $fieldMap = $this->handle(FieldMap::class);

        // ProductImage collection
        foreach ($product->productImage as $productImageDto) {
            $handler->addProductImage($this->handle(FieldMapper::class)->toColumns(get_object_vars($productImageDto), $fieldMap->productImagesColumns()));
        }
    }

    /**
     * Hydrates ProductVariant collection.
     * @throws Throwable
     */
    protected function hydrateProductVariant(Product $handler, CommandProduct $product): void
    {
        $fieldMap = $this->handle(FieldMap::class);

        // ProductVariant collection
        foreach ($product->productVariant as $productVariantDto) {
            $productVariantPriceDataList = [];
            foreach ($productVariantDto->productVariantPrice as $productVariantPriceDto) {
                $productVariantPriceDataList[] = $this->handle(FieldMapper::class)->toColumns(get_object_vars($productVariantPriceDto), $fieldMap->productVariantPricesColumns());
            }
            $entityData = $this->handle(FieldMapper::class)->toColumns([
                ...array_filter(
                    get_object_vars($productVariantDto),
                    fn($key) => !in_array($key, ["productVariantPrice"], true),
                    ARRAY_FILTER_USE_KEY
                ),
            ], $fieldMap->productVariantsColumns());
            if (!empty($productVariantPriceDataList)) {
                $entityData['productVariantPrice'] = $productVariantPriceDataList;
            }
            $handler->addProductVariant($entityData);
        }
    }
}

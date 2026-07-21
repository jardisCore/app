<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Aggregate\Action;

use Ecommerce\Catalog\Aggregate\Product\Aggregate\Entity\ProductVariant;
use Ecommerce\Catalog\Aggregate\Product\Aggregate\Entity\ProductVariantPrice;
use Ecommerce\EcommerceContext;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Catalog\Aggregate\Product\Aggregate\Entity\Product as ProductAggregate;

/**
 * Action: AddProductVariant
 */
class AddProductVariant extends EcommerceContext
{
    /**
     * Adds a ProductVariant to the productVariant collection.
     *
     * @param ProductAggregate $aggregate
     * @param array<string, mixed> $data Entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(ProductAggregate $aggregate, array $data): void
    {
        $hydration = $this->handle(Hydration::class);

        $productVariantPriceDataList = $data['productVariantPrice'] ?? [];
        unset($data['productVariantPrice']);

        $entity = $hydration->hydrate($this->handle(ProductVariant::class), $data);

        foreach ($productVariantPriceDataList as $childData) {
            $entity->addProductVariantPrice($hydration->hydrate($this->handle(ProductVariantPrice::class), $childData));
        }

        $aggregate->addProductVariant($entity);
    }
}

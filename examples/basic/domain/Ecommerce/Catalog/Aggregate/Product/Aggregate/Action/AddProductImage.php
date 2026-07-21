<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Aggregate\Action;

use Ecommerce\Catalog\Aggregate\Product\Aggregate\Entity\ProductImage;
use Ecommerce\EcommerceContext;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Catalog\Aggregate\Product\Aggregate\Entity\Product as ProductAggregate;

/**
 * Action: AddProductImage
 */
class AddProductImage extends EcommerceContext
{
    /**
     * Adds a ProductImage to the productImage collection.
     *
     * @param ProductAggregate $aggregate
     * @param array<string, mixed> $data Entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(ProductAggregate $aggregate, array $data): void
    {
        $hydration = $this->handle(Hydration::class);

        $entity = $hydration->hydrate($this->handle(ProductImage::class), $data);

        $aggregate->addProductImage($entity);
    }
}

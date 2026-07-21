<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Aggregate\Action;

use Ecommerce\Catalog\Aggregate\Product\Aggregate\Entity\ProductVariantPrice;
use Ecommerce\EcommerceContext;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Catalog\Aggregate\Product\Aggregate\Entity\Product as ProductAggregate;

/**
 * Action: AddProductVariantPrice
 */
class AddProductVariantPrice extends EcommerceContext
{
    /**
     * Adds a ProductVariantPrice to the productVariantPrice collection.
     *
     * @param ProductAggregate $aggregate
     * @param string $productVariantIdentifier Parent ProductVariant identifier
     * @param array<string, mixed> $data Entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(ProductAggregate $aggregate, string $productVariantIdentifier, array $data): void
    {
        $hydration = $this->handle(Hydration::class);
        $entity = $hydration->hydrate($this->handle(ProductVariantPrice::class), $data);

        foreach ($aggregate->getProductVariant() as $parent) {
            if ($parent->getIdentifier() === $productVariantIdentifier) {
                $parent->addProductVariantPrice($entity);
                break;
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Aggregate\Entity;

use JardisSupport\Data\Attribute\Aggregate;
use JardisSupport\Data\Attribute\Relation;
use Ecommerce\Catalog\Entity\ProductVariant as EntityProductVariant;

/**
 * Aggregate entity: ProductVariant
 *
 * Extends base entity with aggregate-specific relationships.
 */
#[Aggregate(name: 'ProductVariant')]
class ProductVariant extends EntityProductVariant
{
    /**
     * @var ProductVariantPrice[]
     */
    #[Relation(type: 'many', target: ProductVariantPrice::class)]
    private array $productVariantPrice = [];

    /**
     * @return ProductVariantPrice[]
     */
    public function getProductVariantPrice(): array
    {
        return $this->productVariantPrice;
    }

    public function addProductVariantPrice(ProductVariantPrice $productVariantPrice): self
    {
        $this->productVariantPrice[] = $productVariantPrice;
        return $this;
    }

    public function removeProductVariantPrice(ProductVariantPrice $productVariantPrice): self
    {
        $this->productVariantPrice = array_values(
            array_filter($this->productVariantPrice, fn($existing) => $existing !== $productVariantPrice)
        );
        return $this;
    }
}

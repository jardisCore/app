<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Aggregate\Entity;

use JardisSupport\Data\Attribute\Aggregate;
use JardisSupport\Data\Attribute\Relation;
use Ecommerce\Catalog\Entity\Product as EntityProduct;

/**
 * Root aggregate: Product
 *
 * Extends base entity with aggregate-specific relationships.
 */
#[Aggregate(name: 'Product', root: true)]
class Product extends EntityProduct
{
    /**
     * @var ProductImage[]
     */
    #[Relation(type: 'many', target: ProductImage::class)]
    private array $productImage = [];

    /**
     * @var ProductVariant[]
     */
    #[Relation(type: 'many', target: ProductVariant::class)]
    private array $productVariant = [];

    /**
     * @return ProductImage[]
     */
    public function getProductImage(): array
    {
        return $this->productImage;
    }

    /**
     * @return ProductVariant[]
     */
    public function getProductVariant(): array
    {
        return $this->productVariant;
    }

    public function addProductImage(ProductImage $productImage): self
    {
        $this->productImage[] = $productImage;
        return $this;
    }

    public function removeProductImage(ProductImage $productImage): self
    {
        $this->productImage = array_values(
            array_filter($this->productImage, fn($existing) => $existing !== $productImage)
        );
        return $this;
    }

    public function addProductVariant(ProductVariant $productVariant): self
    {
        $this->productVariant[] = $productVariant;
        return $this;
    }

    public function removeProductVariant(ProductVariant $productVariant): self
    {
        $this->productVariant = array_values(
            array_filter($this->productVariant, fn($existing) => $existing !== $productVariant)
        );
        return $this;
    }
}

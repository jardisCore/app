<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product;

use Ecommerce\Catalog\Aggregate\Product\Command\Handler\AddProductImage;
use Ecommerce\Catalog\Aggregate\Product\Command\Handler\AddProductVariant;
use Ecommerce\Catalog\Aggregate\Product\Command\Handler\AddProductVariantPrice;
use Ecommerce\Catalog\Aggregate\Product\Command\Handler\CreateProduct;
use Ecommerce\Catalog\Aggregate\Product\Command\Handler\RemoveProduct;
use Ecommerce\Catalog\Aggregate\Product\Command\Handler\RemoveProductImage;
use Ecommerce\Catalog\Aggregate\Product\Command\Handler\RemoveProductVariant;
use Ecommerce\Catalog\Aggregate\Product\Command\Handler\RemoveProductVariantPrice;
use Ecommerce\Catalog\Aggregate\Product\Command\Handler\UpdateProduct;
use Ecommerce\Catalog\Aggregate\Product\Event\ProductEvents;
use Ecommerce\EcommerceContext;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use Throwable;
use Ecommerce\Catalog\Aggregate\Product\Command\AddProductImage as CommandAddProductImage;
use Ecommerce\Catalog\Aggregate\Product\Command\AddProductVariant as CommandAddProductVariant;
use Ecommerce\Catalog\Aggregate\Product\Command\AddProductVariantPrice as CommandAddProductVariantPrice;
use Ecommerce\Catalog\Aggregate\Product\Command\Product as CommandProduct;
use Ecommerce\Catalog\Aggregate\Product\Command\RemoveProduct as CommandRemoveProduct;
use Ecommerce\Catalog\Aggregate\Product\Command\RemoveProductImage as CommandRemoveProductImage;
use Ecommerce\Catalog\Aggregate\Product\Command\RemoveProductVariant as CommandRemoveProductVariant;
use Ecommerce\Catalog\Aggregate\Product\Command\RemoveProductVariantPrice as CommandRemoveProductVariantPrice;
use Ecommerce\Catalog\Aggregate\Product\Command\UpdateProduct as CommandUpdateProduct;

/**
 * Product Aggregate Facade.
 *
 * Hosts the inline command operations for this aggregate (writes).
 * Domain events are exposed via event() (constants on ProductEvents).
 * Query/list operations live on the sibling read facade.
 */
class Product extends EcommerceContext
{
    /**
     * Creates a new Product.
     *
     * @param CommandProduct $product
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function createProduct(CommandProduct $product, string $version = ''): DomainResponseInterface
    {
        return $this->context(CreateProduct::class, $product, $version)();
    }

    /**
     * Update Product operation.
     *
     * @param CommandUpdateProduct $updateProduct
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function updateProduct(CommandUpdateProduct $updateProduct, string $version = ''): DomainResponseInterface
    {
        return $this->context(UpdateProduct::class, $updateProduct, $version)();
    }

    /**
     * Add ProductImage operation.
     *
     * @param CommandAddProductImage $addProductImage
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function addProductImage(
        CommandAddProductImage $addProductImage,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(AddProductImage::class, $addProductImage, $version)();
    }

    /**
     * Remove ProductImage operation.
     *
     * @param CommandRemoveProductImage $removeProductImage
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function removeProductImage(
        CommandRemoveProductImage $removeProductImage,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(RemoveProductImage::class, $removeProductImage, $version)();
    }

    /**
     * Add ProductVariant operation.
     *
     * @param CommandAddProductVariant $addProductVariant
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function addProductVariant(
        CommandAddProductVariant $addProductVariant,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(AddProductVariant::class, $addProductVariant, $version)();
    }

    /**
     * Remove ProductVariant operation.
     *
     * @param CommandRemoveProductVariant $removeProductVariant
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function removeProductVariant(
        CommandRemoveProductVariant $removeProductVariant,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(RemoveProductVariant::class, $removeProductVariant, $version)();
    }

    /**
     * Add ProductVariantPrice operation.
     *
     * @param CommandAddProductVariantPrice $addProductVariantPrice
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function addProductVariantPrice(
        CommandAddProductVariantPrice $addProductVariantPrice,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(AddProductVariantPrice::class, $addProductVariantPrice, $version)();
    }

    /**
     * Remove ProductVariantPrice operation.
     *
     * @param CommandRemoveProductVariantPrice $removeProductVariantPrice
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function removeProductVariantPrice(
        CommandRemoveProductVariantPrice $removeProductVariantPrice,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(RemoveProductVariantPrice::class, $removeProductVariantPrice, $version)();
    }

    /**
     * Removes Product aggregate.
     *
     * @param CommandRemoveProduct $removeProduct
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function removeProduct(CommandRemoveProduct $removeProduct, string $version = ''): DomainResponseInterface
    {
        return $this->context(RemoveProduct::class, $removeProduct, $version)();
    }

    /**
     * Returns the Event registry.
     *
     * @return ProductEvents
     * @throws Throwable
     */
    public function event(): ProductEvents
    {
        return $this->handle(ProductEvents::class);
    }
}

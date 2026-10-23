<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Event;

use JardisSupport\Contract\EventListener\EventListenerRegistryInterface;

/**
 * Event routing for the Product aggregate.
 *
 * Configure how domain events are transported to consumers.
 * Each event is registered with an empty listener; listeners and transport
 * are wired in the App delivery layer, never in this generated file.
 *
 * Channel keys:
 *   ecommerce.catalog.product.created
 *   ecommerce.catalog.product.removed
 *   ecommerce.catalog.product.updated
 *   ecommerce.catalog.product.product-image.added
 *   ecommerce.catalog.product.product-image.removed
 *   ecommerce.catalog.product.product-image.updated
 *   ecommerce.catalog.product.product-variant.added
 *   ecommerce.catalog.product.product-variant.removed
 *   ecommerce.catalog.product.product-variant.updated
 *   ecommerce.catalog.product.product-variant-price.added
 *   ecommerce.catalog.product.product-variant-price.removed
 *   ecommerce.catalog.product.product-variant-price.updated
 */
class ProductEventRouter
{
    public function __invoke(EventListenerRegistryInterface $registry): void
    {
        $this->onProductCreated($registry);
        $this->onProductRemoved($registry);
        $this->onProductUpdated($registry);
        $this->onProductProductImageAdded($registry);
        $this->onProductProductImageRemoved($registry);
        $this->onProductProductImageUpdated($registry);
        $this->onProductProductVariantAdded($registry);
        $this->onProductProductVariantRemoved($registry);
        $this->onProductProductVariantUpdated($registry);
        $this->onProductProductVariantPriceAdded($registry);
        $this->onProductProductVariantPriceRemoved($registry);
        $this->onProductProductVariantPriceUpdated($registry);
    }

    // ecommerce.catalog.product.created
    protected function onProductCreated(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(ProductCreated::class, function (ProductCreated $event) {
            // configure transport
        });
    }

    // ecommerce.catalog.product.removed
    protected function onProductRemoved(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(ProductRemoved::class, function (ProductRemoved $event) {
            // configure transport
        });
    }

    // ecommerce.catalog.product.updated
    protected function onProductUpdated(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(ProductUpdated::class, function (ProductUpdated $event) {
            // configure transport
        });
    }

    // ecommerce.catalog.product.product-image.added
    protected function onProductProductImageAdded(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(ProductProductImageAdded::class, function (ProductProductImageAdded $event) {
            // configure transport
        });
    }

    // ecommerce.catalog.product.product-image.removed
    protected function onProductProductImageRemoved(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(ProductProductImageRemoved::class, function (ProductProductImageRemoved $event) {
            // configure transport
        });
    }

    // ecommerce.catalog.product.product-image.updated
    protected function onProductProductImageUpdated(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(ProductProductImageUpdated::class, function (ProductProductImageUpdated $event) {
            // configure transport
        });
    }

    // ecommerce.catalog.product.product-variant.added
    protected function onProductProductVariantAdded(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(ProductProductVariantAdded::class, function (ProductProductVariantAdded $event) {
            // configure transport
        });
    }

    // ecommerce.catalog.product.product-variant.removed
    protected function onProductProductVariantRemoved(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(ProductProductVariantRemoved::class, function (ProductProductVariantRemoved $event) {
            // configure transport
        });
    }

    // ecommerce.catalog.product.product-variant.updated
    protected function onProductProductVariantUpdated(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(ProductProductVariantUpdated::class, function (ProductProductVariantUpdated $event) {
            // configure transport
        });
    }

    // ecommerce.catalog.product.product-variant-price.added
    protected function onProductProductVariantPriceAdded(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(ProductProductVariantPriceAdded::class, function (ProductProductVariantPriceAdded $event) {
            // configure transport
        });
    }

    // ecommerce.catalog.product.product-variant-price.removed
    protected function onProductProductVariantPriceRemoved(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(
            ProductProductVariantPriceRemoved::class,
            function (ProductProductVariantPriceRemoved $event) {
                // configure transport
            }
        );
    }

    // ecommerce.catalog.product.product-variant-price.updated
    protected function onProductProductVariantPriceUpdated(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(
            ProductProductVariantPriceUpdated::class,
            function (ProductProductVariantPriceUpdated $event) {
                // configure transport
            }
        );
    }
}

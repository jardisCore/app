<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Event;

use JardisSupport\Contract\EventListener\EventListenerRegistryInterface;

/**
 * Event routing for the Product aggregate.
 *
 * Configure how domain events are transported to consumers.
 * Each event is registered with an empty listener — fill in the transport logic.
 *
 * Channel keys:
 *   ecommerce.catalog.product.created
 *   ecommerce.catalog.product.removed
 *   ecommerce.catalog.product.updated
 *   ecommerce.catalog.product.product-image.added
 *   ecommerce.catalog.product.product-image.removed
 *   ecommerce.catalog.product.product-variant.added
 *   ecommerce.catalog.product.product-variant.removed
 *   ecommerce.catalog.product.product-variant-price.added
 *   ecommerce.catalog.product.product-variant-price.removed
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
        $this->onProductProductVariantAdded($registry);
        $this->onProductProductVariantRemoved($registry);
        $this->onProductProductVariantPriceAdded($registry);
        $this->onProductProductVariantPriceRemoved($registry);
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
        $registry->listen(ProductProductVariantPriceRemoved::class, function (ProductProductVariantPriceRemoved $event) {
            // configure transport
        });
    }
}

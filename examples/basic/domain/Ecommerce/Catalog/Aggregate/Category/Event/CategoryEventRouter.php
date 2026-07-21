<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Event;

use JardisSupport\Contract\EventListener\EventListenerRegistryInterface;

/**
 * Event routing for the Category aggregate.
 *
 * Configure how domain events are transported to consumers.
 * Each event is registered with an empty listener — fill in the transport logic.
 *
 * Channel keys:
 *   ecommerce.catalog.category.created
 *   ecommerce.catalog.category.removed
 *   ecommerce.catalog.category.updated
 *   ecommerce.catalog.category.category-translation.added
 *   ecommerce.catalog.category.category-translation.removed
 */
class CategoryEventRouter
{
    public function __invoke(EventListenerRegistryInterface $registry): void
    {
        $this->onCategoryCreated($registry);
        $this->onCategoryRemoved($registry);
        $this->onCategoryUpdated($registry);
        $this->onCategoryCategoryTranslationAdded($registry);
        $this->onCategoryCategoryTranslationRemoved($registry);
    }

    // ecommerce.catalog.category.created
    protected function onCategoryCreated(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(CategoryCreated::class, function (CategoryCreated $event) {
            // configure transport
        });
    }

    // ecommerce.catalog.category.removed
    protected function onCategoryRemoved(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(CategoryRemoved::class, function (CategoryRemoved $event) {
            // configure transport
        });
    }

    // ecommerce.catalog.category.updated
    protected function onCategoryUpdated(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(CategoryUpdated::class, function (CategoryUpdated $event) {
            // configure transport
        });
    }

    // ecommerce.catalog.category.category-translation.added
    protected function onCategoryCategoryTranslationAdded(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(CategoryCategoryTranslationAdded::class, function (CategoryCategoryTranslationAdded $event) {
            // configure transport
        });
    }

    // ecommerce.catalog.category.category-translation.removed
    protected function onCategoryCategoryTranslationRemoved(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(CategoryCategoryTranslationRemoved::class, function (CategoryCategoryTranslationRemoved $event) {
            // configure transport
        });
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category;

use Ecommerce\Catalog\Aggregate\Category\Command\Handler\AddCategoryTranslation;
use Ecommerce\Catalog\Aggregate\Category\Command\Handler\CreateCategory;
use Ecommerce\Catalog\Aggregate\Category\Command\Handler\RemoveCategory;
use Ecommerce\Catalog\Aggregate\Category\Command\Handler\RemoveCategoryTranslation;
use Ecommerce\Catalog\Aggregate\Category\Command\Handler\UpdateCategory;
use Ecommerce\Catalog\Aggregate\Category\Event\CategoryEvents;
use Ecommerce\EcommerceContext;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use Throwable;
use Ecommerce\Catalog\Aggregate\Category\Command\AddCategoryTranslation as CommandAddCategoryTranslation;
use Ecommerce\Catalog\Aggregate\Category\Command\Category as CommandCategory;
use Ecommerce\Catalog\Aggregate\Category\Command\RemoveCategory as CommandRemoveCategory;
use Ecommerce\Catalog\Aggregate\Category\Command\RemoveCategoryTranslation as CommandRemoveCategoryTranslation;
use Ecommerce\Catalog\Aggregate\Category\Command\UpdateCategory as CommandUpdateCategory;

/**
 * Category Aggregate Facade.
 *
 * Hosts the inline command operations for this aggregate (writes).
 * Domain events are exposed via event() (constants on CategoryEvents).
 * Query/list operations live on the sibling read facade.
 */
class Category extends EcommerceContext
{
    /**
     * Creates a new Category.
     *
     * @param CommandCategory $category
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function createCategory(CommandCategory $category, string $version = ''): DomainResponseInterface
    {
        return $this->context(CreateCategory::class, $category, $version)();
    }

    /**
     * Update Category operation.
     *
     * @param CommandUpdateCategory $updateCategory
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function updateCategory(CommandUpdateCategory $updateCategory, string $version = ''): DomainResponseInterface
    {
        return $this->context(UpdateCategory::class, $updateCategory, $version)();
    }

    /**
     * Add CategoryTranslation operation.
     *
     * @param CommandAddCategoryTranslation $addCategoryTranslation
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function addCategoryTranslation(
        CommandAddCategoryTranslation $addCategoryTranslation,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(AddCategoryTranslation::class, $addCategoryTranslation, $version)();
    }

    /**
     * Remove CategoryTranslation operation.
     *
     * @param CommandRemoveCategoryTranslation $removeCategoryTranslation
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function removeCategoryTranslation(
        CommandRemoveCategoryTranslation $removeCategoryTranslation,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(RemoveCategoryTranslation::class, $removeCategoryTranslation, $version)();
    }

    /**
     * Removes Category aggregate.
     *
     * @param CommandRemoveCategory $removeCategory
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function removeCategory(CommandRemoveCategory $removeCategory, string $version = ''): DomainResponseInterface
    {
        return $this->context(RemoveCategory::class, $removeCategory, $version)();
    }

    /**
     * Returns the Event registry.
     *
     * @return CategoryEvents
     * @throws Throwable
     */
    public function event(): CategoryEvents
    {
        return $this->handle(CategoryEvents::class);
    }
}

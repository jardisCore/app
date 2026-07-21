<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Command\Handler\Action;

use Ecommerce\Catalog\Aggregate\Category\Aggregate\Category;
use Ecommerce\Catalog\FieldMap;
use Ecommerce\EcommerceContext;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Catalog\Aggregate\Category\Command\Category as CommandCategory;

/**
 * Action: HydrateCreateCategoryEntities
 */
class HydrateCreateCategoryEntities extends EcommerceContext
{
    /**
     * Hydrates all entities from the command DTO.
     *
     * @param Category $handler Aggregate handler
     * @param CommandCategory $category Command data
     * @throws Throwable
     */
    public function __invoke(Category $handler, CommandCategory $category): void
    {
        $this->hydrateCategory($handler, $category);
        $this->hydrateCategoryTranslation($handler, $category);
    }

    /**
     * Hydrates Category entity data.
     * @throws Throwable
     */
    protected function hydrateCategory(Category $handler, CommandCategory $category): void
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($category);

        $handler->setCategory($this->handle(FieldMapper::class)->toColumns(array_filter(
            $rawData,
            fn($key) => !in_array($key, ["categoryTranslation"], true),
            ARRAY_FILTER_USE_KEY
        ), $fieldMap->categoriesColumns()));
    }

    /**
     * Hydrates CategoryTranslation collection.
     * @throws Throwable
     */
    protected function hydrateCategoryTranslation(Category $handler, CommandCategory $category): void
    {
        $fieldMap = $this->handle(FieldMap::class);

        // CategoryTranslation collection
        foreach ($category->categoryTranslation as $categoryTranslationDto) {
            $handler->addCategoryTranslation($this->handle(FieldMapper::class)->toColumns(get_object_vars($categoryTranslationDto), $fieldMap->categoryTranslationsColumns()));
        }
    }
}

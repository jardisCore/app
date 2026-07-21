<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Aggregate\Entity;

use JardisSupport\Data\Attribute\Aggregate;
use JardisSupport\Data\Attribute\Relation;
use Ecommerce\Catalog\Entity\Category as EntityCategory;

/**
 * Root aggregate: Category
 *
 * Extends base entity with aggregate-specific relationships.
 */
#[Aggregate(name: 'Category', root: true)]
class Category extends EntityCategory
{
    /**
     * @var CategoryTranslation[]
     */
    #[Relation(type: 'many', target: CategoryTranslation::class)]
    private array $categoryTranslation = [];

    /**
     * @return CategoryTranslation[]
     */
    public function getCategoryTranslation(): array
    {
        return $this->categoryTranslation;
    }

    public function addCategoryTranslation(CategoryTranslation $categoryTranslation): self
    {
        $this->categoryTranslation[] = $categoryTranslation;
        return $this;
    }

    public function removeCategoryTranslation(CategoryTranslation $categoryTranslation): self
    {
        $this->categoryTranslation = array_values(
            array_filter($this->categoryTranslation, fn($existing) => $existing !== $categoryTranslation)
        );
        return $this;
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Aggregate\Action;

use Ecommerce\Catalog\Aggregate\Category\Aggregate\Entity\CategoryTranslation;
use Ecommerce\EcommerceContext;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Catalog\Aggregate\Category\Aggregate\Entity\Category as CategoryAggregate;

/**
 * Action: AddCategoryTranslation
 */
class AddCategoryTranslation extends EcommerceContext
{
    /**
     * Adds a CategoryTranslation to the categoryTranslation collection.
     *
     * @param CategoryAggregate $aggregate
     * @param array<string, mixed> $data Entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(CategoryAggregate $aggregate, array $data): void
    {
        $hydration = $this->handle(Hydration::class);

        $entity = $hydration->hydrate($this->handle(CategoryTranslation::class), $data);

        $aggregate->addCategoryTranslation($entity);
    }
}

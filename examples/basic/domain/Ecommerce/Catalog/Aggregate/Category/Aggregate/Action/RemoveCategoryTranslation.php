<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Category\Aggregate\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Catalog\Aggregate\Category\Aggregate\Entity\Category as CategoryAggregate;

/**
 * Action: RemoveCategoryTranslation
 */
class RemoveCategoryTranslation extends EcommerceContext
{
    /**
     * Removes a CategoryTranslation from the categoryTranslation collection.
     *
     * @param CategoryAggregate $aggregate
     * @param int $id
     * @return array<array{string, object|null}> Entities to track for deletion
     */
    public function __invoke(CategoryAggregate $aggregate, int $id): array
    {
        $removals = [];
        $collection = $aggregate->getCategoryTranslation();

        foreach ($collection as $item) {
            $primaryKey = $item::PRIMARY_KEY;
            $getter = 'get' . ucfirst($primaryKey);
            $itemId = $item->$getter();
            if ($itemId !== null && $itemId === $id) {
                $removals[] = ['categoryTranslation', $item];
                $aggregate->removeCategoryTranslation($item);
                break;
            }
        }
        return $removals;
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Category\Aggregate\Action;

use Ecommerce\EcommerceContext;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Catalog\Model\Category\Aggregate\Entity\Category as CategoryAggregate;

/**
 * Action: UpdateCategoryTranslation
 */
class UpdateCategoryTranslation extends EcommerceContext
{
    /**
     * Updates a CategoryTranslation in the categoryTranslation collection.
     *
     * @param CategoryAggregate $aggregate
     * @param int $id
     * @param array<string, mixed> $data Entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(CategoryAggregate $aggregate, int $id, array $data): void
    {
        $hydration = $this->handle(Hydration::class);

        foreach ($aggregate->getCategoryTranslation() as $item) {
            $primaryKey = $item::PRIMARY_KEY;
            $getter = 'get' . ucfirst($primaryKey);
            $itemId = $item->$getter();
            if ($itemId !== null && $itemId === $id) {
                // apply() sets the values WITHOUT updating the snapshot: change tracking and the
                // expected-values check (stale write -> 409) stay in effect.
                $hydration->apply($item, $data);
                break;
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Aggregate\Action;

use Ecommerce\EcommerceContext;
use RuntimeException;
use Ecommerce\Catalog\Aggregate\Product\Aggregate\Entity\Product as ProductAggregate;

/**
 * Action: RemoveProductVariant
 */
class RemoveProductVariant extends EcommerceContext
{
    /**
     * Removes a ProductVariant from the productVariant collection.
     *
     * @param ProductAggregate $aggregate
     * @param int $id
     * @return array<array{string, object|null}> Entities to track for deletion
     * @throws RuntimeException
     */
    public function __invoke(ProductAggregate $aggregate, int $id): array
    {
        $removals = [];
        $collection = $aggregate->getProductVariant();

        foreach ($collection as $item) {
            $primaryKey = $item::PRIMARY_KEY;
            $getter = 'get' . ucfirst($primaryKey);
            $itemId = $item->$getter();
            if ($itemId !== null && $itemId === $id) {
                if (count($collection) <= 1) {
                    throw new RuntimeException('Cannot remove the last ProductVariant: at least one is required.');
                }
                $removals[] = ['productVariant', $item];
                $aggregate->removeProductVariant($item);
                break;
            }
        }
        return $removals;
    }
}

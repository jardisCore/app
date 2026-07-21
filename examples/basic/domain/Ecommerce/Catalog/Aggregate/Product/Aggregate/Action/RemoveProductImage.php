<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Aggregate\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Catalog\Aggregate\Product\Aggregate\Entity\Product as ProductAggregate;

/**
 * Action: RemoveProductImage
 */
class RemoveProductImage extends EcommerceContext
{
    /**
     * Removes a ProductImage from the productImage collection.
     *
     * @param ProductAggregate $aggregate
     * @param int $id
     * @return array<array{string, object|null}> Entities to track for deletion
     */
    public function __invoke(ProductAggregate $aggregate, int $id): array
    {
        $removals = [];
        $collection = $aggregate->getProductImage();

        foreach ($collection as $item) {
            $primaryKey = $item::PRIMARY_KEY;
            $getter = 'get' . ucfirst($primaryKey);
            $itemId = $item->$getter();
            if ($itemId !== null && $itemId === $id) {
                $removals[] = ['productImage', $item];
                $aggregate->removeProductImage($item);
                break;
            }
        }
        return $removals;
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Aggregate\Product\Aggregate\Action;

use Ecommerce\EcommerceContext;
use RuntimeException;
use Ecommerce\Catalog\Aggregate\Product\Aggregate\Entity\Product as ProductAggregate;

/**
 * Action: RemoveProductVariantPrice
 */
class RemoveProductVariantPrice extends EcommerceContext
{
    /**
     * Removes a ProductVariantPrice from the productVariantPrice collection.
     *
     * @param ProductAggregate $aggregate
     * @param int $id
     * @return array<array{string, object|null}> Entities to track for deletion
     * @throws RuntimeException
     */
    public function __invoke(ProductAggregate $aggregate, int $id): array
    {
        $removals = [];
        foreach ($aggregate->getProductVariant() as $parent) {
            $collection = $parent->getProductVariantPrice();
            foreach ($collection as $item) {
                $primaryKey = $item::PRIMARY_KEY;
                $itemGetter = 'get' . ucfirst($primaryKey);
                $itemId = $item->$itemGetter();
                if ($itemId !== null && $itemId === $id) {
                    if (count($collection) <= 1) {
                        throw new RuntimeException('Cannot remove the last ProductVariantPrice: at least one is required.');
                    }
                    $removals[] = ['productVariantPrice', $item];
                    $parent->removeProductVariantPrice($item);
                    return $removals;
                }
            }
        }
        throw new RuntimeException('ProductVariantPrice not found: ' . $id);
    }
}

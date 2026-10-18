<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Aggregate\Action;

use Ecommerce\EcommerceContext;
use RuntimeException;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Ecommerce\Catalog\Model\Product\Aggregate\Entity\Product as ProductAggregate;

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
     * @return array<array{string, object|null}>|ResponseStatus Entities to track for deletion, or NotFound when a path
     *     level is absent
     * @throws RuntimeException
     */
    public function __invoke(ProductAggregate $aggregate, int $id): array|ResponseStatus
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
                        throw new RuntimeException(
                            'Cannot remove the last ProductVariantPrice: at least one is required.'
                        );
                    }
                    $removals[] = ['productVariantPrice', $item];
                    $parent->removeProductVariantPrice($item);
                    return $removals;
                }
            }
        }
        return ResponseStatus::NotFound;
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Catalog\Model\Product\Aggregate\Action;

use Ecommerce\EcommerceContext;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Catalog\Model\Product\Aggregate\Entity\Product as ProductAggregate;

/**
 * Action: UpdateProductVariantPrice
 */
class UpdateProductVariantPrice extends EcommerceContext
{
    /**
     * Updates a ProductVariantPrice in the productVariantPrice collection.
     *
     * @param ProductAggregate $aggregate
     * @param int $id
     * @param array<string, mixed> $data Entity data
     * @return ResponseStatus|null NotFound when a path level is absent, null when applied
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(ProductAggregate $aggregate, int $id, array $data): ?ResponseStatus
    {
        $hydration = $this->handle(Hydration::class);

        foreach ($aggregate->getProductVariant() as $parent) {
            $collection = $parent->getProductVariantPrice();
            foreach ($collection as $item) {
                $primaryKey = $item::PRIMARY_KEY;
                $itemGetter = 'get' . ucfirst($primaryKey);
                $itemId = $item->$itemGetter();
                if ($itemId !== null && $itemId === $id) {
                    // apply() sets the values WITHOUT updating the snapshot: change tracking and the
                    // expected-values check (stale write -> 409) stay in effect.
                    $hydration->apply($item, $data);
                    return null;
                }
            }
        }
        return ResponseStatus::NotFound;
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Aggregate\Action;

use Ecommerce\EcommerceContext;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Sales\Model\Order\Aggregate\Entity\Order as OrderAggregate;

/**
 * Action: UpdateItemDiscount
 */
class UpdateItemDiscount extends EcommerceContext
{
    /**
     * Updates an ItemDiscount in the itemDiscount collection.
     *
     * @param OrderAggregate $aggregate
     * @param int $id
     * @param array<string, mixed> $data Entity data
     * @return ResponseStatus|null NotFound when a path level is absent, null when applied
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(OrderAggregate $aggregate, int $id, array $data): ?ResponseStatus
    {
        $hydration = $this->handle(Hydration::class);

        foreach ($aggregate->getOrderItem() as $parent) {
            $collection = $parent->getItemDiscount();
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

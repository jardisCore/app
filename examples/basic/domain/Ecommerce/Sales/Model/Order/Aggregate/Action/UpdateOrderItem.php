<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Aggregate\Action;

use Ecommerce\EcommerceContext;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Sales\Model\Order\Aggregate\Entity\Order as OrderAggregate;

/**
 * Action: UpdateOrderItem
 */
class UpdateOrderItem extends EcommerceContext
{
    /**
     * Updates an OrderItem in the orderItem collection.
     *
     * @param OrderAggregate $aggregate
     * @param int $id
     * @param array<string, mixed> $data Entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(OrderAggregate $aggregate, int $id, array $data): void
    {
        $hydration = $this->handle(Hydration::class);

        foreach ($aggregate->getOrderItem() as $item) {
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

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Aggregate\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Entity\ItemDiscount;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Entity\OrderItem;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Entity\Order as OrderAggregate;

/**
 * Action: AddOrderItem
 */
class AddOrderItem extends EcommerceContext
{
    /**
     * Adds a OrderItem to the orderItem collection.
     *
     * @param OrderAggregate $aggregate
     * @param array<string, mixed> $data Entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(OrderAggregate $aggregate, array $data): void
    {
        $hydration = $this->handle(Hydration::class);

        $itemDiscountDataList = $data['itemDiscount'] ?? [];
        unset($data['itemDiscount']);

        $entity = $hydration->hydrate($this->handle(OrderItem::class), $data);

        foreach ($itemDiscountDataList as $childData) {
            $entity->addItemDiscount($hydration->hydrate($this->handle(ItemDiscount::class), $childData));
        }

        $aggregate->addOrderItem($entity);
    }
}

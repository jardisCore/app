<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Aggregate\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Entity\ItemDiscount;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Entity\Order as OrderAggregate;

/**
 * Action: AddItemDiscount
 */
class AddItemDiscount extends EcommerceContext
{
    /**
     * Adds a ItemDiscount to the itemDiscount collection.
     *
     * @param OrderAggregate $aggregate
     * @param string $orderItemIdentifier Parent OrderItem identifier
     * @param array<string, mixed> $data Entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(OrderAggregate $aggregate, string $orderItemIdentifier, array $data): void
    {
        $hydration = $this->handle(Hydration::class);
        $entity = $hydration->hydrate($this->handle(ItemDiscount::class), $data);

        foreach ($aggregate->getOrderItem() as $parent) {
            if ($parent->getIdentifier() === $orderItemIdentifier) {
                $parent->addItemDiscount($entity);
                break;
            }
        }
    }
}

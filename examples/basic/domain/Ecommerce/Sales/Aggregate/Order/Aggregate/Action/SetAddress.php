<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Aggregate\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Entity\Address;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Entity\Order as OrderAggregate;

/**
 * Action: SetAddress
 */
class SetAddress extends EcommerceContext
{
    /**
     * Sets/replaces the address entity.
     *
     * @param OrderAggregate $aggregate
     * @param array<string, mixed> $data Entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(OrderAggregate $aggregate, array $data): void
    {
        $hydration = $this->handle(Hydration::class);
        $parent = $aggregate->getCustomer();
        if ($parent !== null) {
            $entity = $parent->getAddress() ?? $this->handle(Address::class);
            $hydration->apply($entity, $data);
            $parent->setAddress($entity);
        }
    }
}

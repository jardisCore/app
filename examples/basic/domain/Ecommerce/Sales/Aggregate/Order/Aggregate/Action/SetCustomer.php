<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Aggregate\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Entity\Customer;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Entity\Order as OrderAggregate;

/**
 * Action: SetCustomer
 */
class SetCustomer extends EcommerceContext
{
    /**
     * Sets/replaces the customer entity.
     *
     * @param OrderAggregate $aggregate
     * @param array<string, mixed> $data Entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(OrderAggregate $aggregate, array $data): void
    {
        $hydration = $this->handle(Hydration::class);
        $entity = $aggregate->getCustomer() ?? $this->handle(Customer::class);
        $hydration->apply($entity, $data);
        $aggregate->setCustomer($entity);
    }
}

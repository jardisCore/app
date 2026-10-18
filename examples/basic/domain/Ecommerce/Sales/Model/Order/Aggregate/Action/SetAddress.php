<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Aggregate\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Order\Aggregate\Entity\Address;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Sales\Model\Order\Aggregate\Entity\Order as OrderAggregate;

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
     * @return ResponseStatus|null NotFound when a path level is absent, null when applied
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(OrderAggregate $aggregate, array $data): ?ResponseStatus
    {
        $hydration = $this->handle(Hydration::class);
        $parent = $aggregate->getCustomer();
        if ($parent === null) {
            return ResponseStatus::NotFound;
        }
        $entity = $parent->getAddress() ?? $this->handle(Address::class);
        $hydration->apply($entity, $data);
        $parent->setAddress($entity);
        return null;
    }
}

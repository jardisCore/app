<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Order\Aggregate\Action;

use Ecommerce\EcommerceContext;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Ecommerce\Sales\Model\Order\Aggregate\Entity\Order as OrderAggregate;

/**
 * Action: RemoveAddress
 */
class RemoveAddress extends EcommerceContext
{
    /**
     * Removes the address entity.
     *
     * @param OrderAggregate $aggregate
     * @return array<array{string, object|null}>|ResponseStatus Entities to track for deletion, or NotFound when a path
     *     level is absent
     */
    public function __invoke(OrderAggregate $aggregate): array|ResponseStatus
    {
        $removals = [];
        $parent = $aggregate->getCustomer();
        if ($parent === null) {
            return ResponseStatus::NotFound;
        }
        $existing = $parent->getAddress();
        if ($existing === null) {
            return ResponseStatus::NotFound;
        }
        $pkGetter = 'get' . ucfirst($existing::PRIMARY_KEY);
        if ($existing->$pkGetter() !== null) {
            $parent->setBillingAddressId(null);
            $removals[] = ['address', $existing];
        }
        $parent->setAddress(null);
        return $removals;
    }
}

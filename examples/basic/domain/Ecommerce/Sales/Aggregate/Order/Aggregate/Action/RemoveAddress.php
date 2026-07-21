<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Aggregate\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Entity\Order as OrderAggregate;

/**
 * Action: RemoveAddress
 */
class RemoveAddress extends EcommerceContext
{
    /**
     * Removes the address entity.
     *
     * @param OrderAggregate $aggregate
     * @return array<array{string, object|null}> Entities to track for deletion
     */
    public function __invoke(OrderAggregate $aggregate): array
    {
        $removals = [];
        $parent = $aggregate->getCustomer();
        if ($parent !== null) {
            $existing = $parent->getAddress();
            if ($existing !== null) {
                $pkGetter = 'get' . ucfirst($existing::PRIMARY_KEY);
                if ($existing->$pkGetter() !== null) {
                    $parent->setBillingAddressId(null);
                    $removals[] = ['address', $existing];
                }
            }
            $parent->setAddress(null);
        }
        return $removals;
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Aggregate\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Order\Aggregate\Entity\Order as OrderAggregate;

/**
 * Action: RemoveCustomer
 */
class RemoveCustomer extends EcommerceContext
{
    /**
     * Removes the customer entity.
     *
     * @param OrderAggregate $aggregate
     * @return array<array{string, object|null}> Entities to track for deletion
     */
    public function __invoke(OrderAggregate $aggregate): array
    {
        $removals = [];
        $existing = $aggregate->getCustomer();
        if ($existing !== null) {
            $pkGetter = 'get' . ucfirst($existing::PRIMARY_KEY);
            if ($existing->$pkGetter() !== null) {
                $aggregate->setCustomerId(null);
                $removals[] = ['customer', $existing];
            }
        }
        $aggregate->setCustomer(null);
        return $removals;
    }
}

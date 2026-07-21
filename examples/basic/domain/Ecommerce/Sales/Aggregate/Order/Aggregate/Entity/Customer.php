<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Aggregate\Entity;

use JardisSupport\Data\Attribute\Aggregate;
use JardisSupport\Data\Attribute\Relation;
use Ecommerce\Sales\Entity\Customer as EntityCustomer;

/**
 * Aggregate entity: Customer
 *
 * Extends base entity with aggregate-specific relationships.
 */
#[Aggregate(name: 'Customer')]
class Customer extends EntityCustomer
{
    #[Relation(type: 'one', target: Address::class)]
    private ?Address $address = null;

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): self
    {
        $this->address = $address;
        return $this;
    }
}

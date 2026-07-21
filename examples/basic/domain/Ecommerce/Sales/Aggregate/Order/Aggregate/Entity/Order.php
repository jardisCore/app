<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Aggregate\Entity;

use JardisSupport\Data\Attribute\Aggregate;
use JardisSupport\Data\Attribute\Relation;
use Ecommerce\Sales\Entity\Order as EntityOrder;

/**
 * Root aggregate: Order
 *
 * Extends base entity with aggregate-specific relationships.
 */
#[Aggregate(name: 'Order', root: true)]
class Order extends EntityOrder
{
    #[Relation(type: 'one', target: Customer::class)]
    private ?Customer $customer = null;

    /**
     * @var OrderItem[]
     */
    #[Relation(type: 'many', target: OrderItem::class)]
    private array $orderItem = [];

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    /**
     * @return OrderItem[]
     */
    public function getOrderItem(): array
    {
        return $this->orderItem;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;
        return $this;
    }

    public function addOrderItem(OrderItem $orderItem): self
    {
        $this->orderItem[] = $orderItem;
        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): self
    {
        $this->orderItem = array_values(
            array_filter($this->orderItem, fn($existing) => $existing !== $orderItem)
        );
        return $this;
    }
}

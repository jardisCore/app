<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Aggregate\Entity;

use JardisSupport\Data\Attribute\Aggregate;
use JardisSupport\Data\Attribute\Relation;
use Ecommerce\Sales\Entity\OrderItem as EntityOrderItem;

/**
 * Aggregate entity: OrderItem
 *
 * Extends base entity with aggregate-specific relationships.
 */
#[Aggregate(name: 'OrderItem')]
class OrderItem extends EntityOrderItem
{
    /**
     * @var ItemDiscount[]
     */
    #[Relation(type: 'many', target: ItemDiscount::class)]
    private array $itemDiscount = [];

    /**
     * @return ItemDiscount[]
     */
    public function getItemDiscount(): array
    {
        return $this->itemDiscount;
    }

    public function addItemDiscount(ItemDiscount $itemDiscount): self
    {
        $this->itemDiscount[] = $itemDiscount;
        return $this;
    }

    public function removeItemDiscount(ItemDiscount $itemDiscount): self
    {
        $this->itemDiscount = array_values(
            array_filter($this->itemDiscount, fn($existing) => $existing !== $itemDiscount)
        );
        return $this;
    }
}

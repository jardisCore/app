<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Aggregate\Entity;

use JardisSupport\Data\Attribute\Aggregate;
use Ecommerce\Sales\Entity\ItemDiscount as EntityItemDiscount;

/**
 * Aggregate entity: ItemDiscount
 *
 * Extends base entity with aggregate-specific relationships.
 */
#[Aggregate(name: 'ItemDiscount')]
class ItemDiscount extends EntityItemDiscount
{
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Aggregate\Entity;

use JardisSupport\Data\Attribute\Aggregate;
use Ecommerce\Sales\Entity\Address as EntityAddress;

/**
 * Aggregate entity: Address
 *
 * Extends base entity with aggregate-specific relationships.
 */
#[Aggregate(name: 'Address')]
class Address extends EntityAddress
{
}

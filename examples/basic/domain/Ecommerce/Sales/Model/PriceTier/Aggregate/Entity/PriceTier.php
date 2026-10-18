<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\PriceTier\Aggregate\Entity;

use JardisSupport\Data\Attribute\Aggregate;
use Ecommerce\Sales\Entity\PriceTier as EntityPriceTier;

/**
 * Root aggregate: PriceTier
 *
 * Extends base entity with aggregate-specific relationships.
 */
#[Aggregate(name: 'PriceTier', root: true)]
class PriceTier extends EntityPriceTier
{
}

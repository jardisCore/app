<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Aggregate\Entity;

use JardisSupport\Data\Attribute\Aggregate;
use Ecommerce\Sales\Entity\ChannelHook as EntityChannelHook;

/**
 * Aggregate entity: ChannelHook
 *
 * Extends base entity with aggregate-specific relationships.
 */
#[Aggregate(name: 'ChannelHook')]
class ChannelHook extends EntityChannelHook
{
}

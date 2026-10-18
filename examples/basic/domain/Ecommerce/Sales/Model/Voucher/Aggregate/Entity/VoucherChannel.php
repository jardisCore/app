<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Aggregate\Entity;

use JardisSupport\Data\Attribute\Aggregate;
use JardisSupport\Data\Attribute\Relation;
use Ecommerce\Sales\Entity\VoucherChannel as EntityVoucherChannel;

/**
 * Aggregate entity: VoucherChannel
 *
 * Extends base entity with aggregate-specific relationships.
 */
#[Aggregate(name: 'VoucherChannel')]
class VoucherChannel extends EntityVoucherChannel
{
    #[Relation(type: 'one', target: ChannelHook::class)]
    private ?ChannelHook $channelHook = null;

    public function getChannelHook(): ?ChannelHook
    {
        return $this->channelHook;
    }

    public function setChannelHook(?ChannelHook $channelHook): self
    {
        $this->channelHook = $channelHook;
        return $this;
    }
}

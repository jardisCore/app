<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Aggregate\Entity;

use JardisSupport\Data\Attribute\Aggregate;
use JardisSupport\Data\Attribute\Relation;
use Ecommerce\Sales\Entity\Voucher as EntityVoucher;

/**
 * Root aggregate: Voucher
 *
 * Extends base entity with aggregate-specific relationships.
 */
#[Aggregate(name: 'Voucher', root: true)]
class Voucher extends EntityVoucher
{
    #[Relation(type: 'one', target: VoucherChannel::class)]
    private ?VoucherChannel $voucherChannel = null;

    public function getVoucherChannel(): ?VoucherChannel
    {
        return $this->voucherChannel;
    }

    public function setVoucherChannel(?VoucherChannel $voucherChannel): self
    {
        $this->voucherChannel = $voucherChannel;
        return $this;
    }
}

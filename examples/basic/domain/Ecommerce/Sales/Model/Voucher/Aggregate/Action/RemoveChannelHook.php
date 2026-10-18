<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Aggregate\Action;

use Ecommerce\EcommerceContext;
use JardisSupport\Contract\Kernel\ResponseStatus;
use Ecommerce\Sales\Model\Voucher\Aggregate\Entity\Voucher as VoucherAggregate;

/**
 * Action: RemoveChannelHook
 */
class RemoveChannelHook extends EcommerceContext
{
    /**
     * Removes the channelHook entity.
     *
     * @param VoucherAggregate $aggregate
     * @return array<array{string, object|null}>|ResponseStatus Entities to track for deletion, or NotFound when a path
     *     level is absent
     */
    public function __invoke(VoucherAggregate $aggregate): array|ResponseStatus
    {
        $removals = [];
        $parent = $aggregate->getVoucherChannel();
        if ($parent === null) {
            return ResponseStatus::NotFound;
        }
        $existing = $parent->getChannelHook();
        if ($existing === null) {
            return ResponseStatus::NotFound;
        }
        $pkGetter = 'get' . ucfirst($existing::PRIMARY_KEY);
        if ($existing->$pkGetter() !== null) {
            $removals[] = ['channelHook', $existing];
        }
        $parent->setChannelHook(null);
        return $removals;
    }
}

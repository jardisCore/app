<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Aggregate\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Voucher\Aggregate\Entity\ChannelHook;
use JardisSupport\Contract\Kernel\ResponseStatus;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Sales\Model\Voucher\Aggregate\Entity\Voucher as VoucherAggregate;

/**
 * Action: SetChannelHook
 */
class SetChannelHook extends EcommerceContext
{
    /**
     * Sets/replaces the channelHook entity.
     *
     * @param VoucherAggregate $aggregate
     * @param array<string, mixed> $data Entity data
     * @return ResponseStatus|null NotFound when a path level is absent, null when applied
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(VoucherAggregate $aggregate, array $data): ?ResponseStatus
    {
        $hydration = $this->handle(Hydration::class);
        $parent = $aggregate->getVoucherChannel();
        if ($parent === null) {
            return ResponseStatus::NotFound;
        }
        $entity = $parent->getChannelHook() ?? $this->handle(ChannelHook::class);
        $hydration->apply($entity, $data);
        $parent->setChannelHook($entity);
        return null;
    }
}

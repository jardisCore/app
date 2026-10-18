<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Aggregate\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Voucher\Aggregate\Entity\VoucherChannel;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Sales\Model\Voucher\Aggregate\Entity\Voucher as VoucherAggregate;

/**
 * Action: SetVoucherChannel
 */
class SetVoucherChannel extends EcommerceContext
{
    /**
     * Sets/replaces the voucherChannel entity.
     *
     * @param VoucherAggregate $aggregate
     * @param array<string, mixed> $data Entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(VoucherAggregate $aggregate, array $data): void
    {
        $hydration = $this->handle(Hydration::class);
        $entity = $aggregate->getVoucherChannel() ?? $this->handle(VoucherChannel::class);
        $hydration->apply($entity, $data);
        $aggregate->setVoucherChannel($entity);
    }
}

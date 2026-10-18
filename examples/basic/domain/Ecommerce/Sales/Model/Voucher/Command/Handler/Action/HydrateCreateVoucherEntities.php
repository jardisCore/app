<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\FieldMap;
use Ecommerce\Sales\Model\Voucher\Aggregate\Voucher;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Sales\Model\Voucher\Command\Voucher as CommandVoucher;

/**
 * Action: HydrateCreateVoucherEntities
 */
class HydrateCreateVoucherEntities extends EcommerceContext
{
    /**
     * Hydrates all entities from the command DTO.
     *
     * @param Voucher $handler Aggregate handler
     * @param CommandVoucher $voucher Command data
     * @throws Throwable
     */
    public function __invoke(Voucher $handler, CommandVoucher $voucher): void
    {
        $this->hydrateVoucher($handler, $voucher);
        $this->hydrateVoucherChannel($handler, $voucher);
        $this->hydrateChannelHook($handler, $voucher);
    }

    /**
     * Hydrates Voucher entity data.
     * @throws Throwable
     */
    protected function hydrateVoucher(Voucher $handler, CommandVoucher $voucher): void
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($voucher);

        $handler->setVoucher($this->handle(FieldMapper::class)->toColumns(array_filter(
            $rawData,
            fn($key) => !in_array($key, ["voucherChannel"], true),
            ARRAY_FILTER_USE_KEY
        ), $fieldMap->vouchersColumns()));
    }

    /**
     * Hydrates VoucherChannel entity data.
     * @throws Throwable
     */
    protected function hydrateVoucherChannel(Voucher $handler, CommandVoucher $voucher): void
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($voucher->voucherChannel);

        $handler->setVoucherChannel($this->handle(FieldMapper::class)->toColumns(array_filter(
            $rawData,
            fn($key) => !in_array($key, ["channelHook"], true),
            ARRAY_FILTER_USE_KEY
        ), $fieldMap->voucherChannelsColumns()));
    }

    /**
     * Hydrates ChannelHook entity data.
     * @throws Throwable
     */
    protected function hydrateChannelHook(Voucher $handler, CommandVoucher $voucher): void
    {
        $fieldMap = $this->handle(FieldMap::class);

        $handler->setChannelHook(
            $this->handle(FieldMapper::class)->toColumns(
                get_object_vars($voucher->voucherChannel->channelHook),
                $fieldMap->channelHooksColumns()
            )
        );
    }
}

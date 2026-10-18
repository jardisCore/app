<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Voucher\Command\Handler\CreateVoucher;
use Ecommerce\Sales\Model\Voucher\Command\Handler\RemoveChannelHook;
use Ecommerce\Sales\Model\Voucher\Command\Handler\RemoveVoucher;
use Ecommerce\Sales\Model\Voucher\Command\Handler\RemoveVoucherChannel;
use Ecommerce\Sales\Model\Voucher\Command\Handler\SetChannelHook;
use Ecommerce\Sales\Model\Voucher\Command\Handler\SetVoucherChannel;
use Ecommerce\Sales\Model\Voucher\Command\Handler\UpdateVoucher;
use Ecommerce\Sales\Model\Voucher\Event\VoucherEvents;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use Throwable;
use Ecommerce\Sales\Model\Voucher\Command\RemoveChannelHook as CommandRemoveChannelHook;
use Ecommerce\Sales\Model\Voucher\Command\RemoveVoucher as CommandRemoveVoucher;
use Ecommerce\Sales\Model\Voucher\Command\RemoveVoucherChannel as CommandRemoveVoucherChannel;
use Ecommerce\Sales\Model\Voucher\Command\SetChannelHook as CommandSetChannelHook;
use Ecommerce\Sales\Model\Voucher\Command\SetVoucherChannel as CommandSetVoucherChannel;
use Ecommerce\Sales\Model\Voucher\Command\UpdateVoucher as CommandUpdateVoucher;
use Ecommerce\Sales\Model\Voucher\Command\Voucher as CommandVoucher;

/**
 * Voucher Aggregate Facade.
 *
 * Hosts the inline command operations for this aggregate (writes).
 * Domain events are exposed via event() (constants on VoucherEvents).
 * Query/list operations live on the sibling read facade.
 */
class Voucher extends EcommerceContext
{
    /**
     * Creates a new Voucher.
     *
     * @param CommandVoucher $voucher
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function createVoucher(CommandVoucher $voucher, string $version = ''): DomainResponseInterface
    {
        return $this->context(CreateVoucher::class, $voucher, $version)();
    }

    /**
     * Update Voucher operation.
     *
     * @param CommandUpdateVoucher $updateVoucher
     * @param string $version Optional ClassVersion override (default: caller version)
     * @param array<string, mixed> $expected Optional read basis (CAS): outer field name => value the caller read
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function updateVoucher(
        CommandUpdateVoucher $updateVoucher,
        string $version = '',
        array $expected = []
    ): DomainResponseInterface {
        return $this->context(UpdateVoucher::class, $updateVoucher, $version)($expected);
    }

    /**
     * Set VoucherChannel operation.
     *
     * @param CommandSetVoucherChannel $setVoucherChannel
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function setVoucherChannel(
        CommandSetVoucherChannel $setVoucherChannel,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(SetVoucherChannel::class, $setVoucherChannel, $version)();
    }

    /**
     * Remove VoucherChannel operation.
     *
     * @param CommandRemoveVoucherChannel $removeVoucherChannel
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function removeVoucherChannel(
        CommandRemoveVoucherChannel $removeVoucherChannel,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(RemoveVoucherChannel::class, $removeVoucherChannel, $version)();
    }

    /**
     * Set ChannelHook operation.
     *
     * @param CommandSetChannelHook $setChannelHook
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function setChannelHook(CommandSetChannelHook $setChannelHook, string $version = ''): DomainResponseInterface
    {
        return $this->context(SetChannelHook::class, $setChannelHook, $version)();
    }

    /**
     * Remove ChannelHook operation.
     *
     * @param CommandRemoveChannelHook $removeChannelHook
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function removeChannelHook(
        CommandRemoveChannelHook $removeChannelHook,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(RemoveChannelHook::class, $removeChannelHook, $version)();
    }

    /**
     * Removes Voucher aggregate.
     *
     * @param CommandRemoveVoucher $removeVoucher
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function removeVoucher(CommandRemoveVoucher $removeVoucher, string $version = ''): DomainResponseInterface
    {
        return $this->context(RemoveVoucher::class, $removeVoucher, $version)();
    }

    /**
     * Returns the Event registry.
     *
     * @return VoucherEvents
     * @throws Throwable
     */
    public function event(): VoucherEvents
    {
        return $this->handle(VoucherEvents::class);
    }
}

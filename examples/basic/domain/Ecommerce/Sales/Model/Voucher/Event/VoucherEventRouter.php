<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Event;

use JardisSupport\Contract\EventListener\EventListenerRegistryInterface;

/**
 * Event routing for the Voucher aggregate.
 *
 * Configure how domain events are transported to consumers.
 * Each event is registered with an empty listener — fill in the transport logic.
 *
 * Channel keys:
 *   ecommerce.sales.voucher.created
 *   ecommerce.sales.voucher.removed
 *   ecommerce.sales.voucher.updated
 *   ecommerce.sales.voucher.voucher-channel.updated
 *   ecommerce.sales.voucher.voucher-channel.removed
 *   ecommerce.sales.voucher.channel-hook.updated
 *   ecommerce.sales.voucher.channel-hook.removed
 */
class VoucherEventRouter
{
    public function __invoke(EventListenerRegistryInterface $registry): void
    {
        $this->onVoucherCreated($registry);
        $this->onVoucherRemoved($registry);
        $this->onVoucherUpdated($registry);
        $this->onVoucherVoucherChannelUpdated($registry);
        $this->onVoucherVoucherChannelRemoved($registry);
        $this->onVoucherChannelHookUpdated($registry);
        $this->onVoucherChannelHookRemoved($registry);
    }

    // ecommerce.sales.voucher.created
    protected function onVoucherCreated(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(VoucherCreated::class, function (VoucherCreated $event) {
            // configure transport
        });
    }

    // ecommerce.sales.voucher.removed
    protected function onVoucherRemoved(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(VoucherRemoved::class, function (VoucherRemoved $event) {
            // configure transport
        });
    }

    // ecommerce.sales.voucher.updated
    protected function onVoucherUpdated(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(VoucherUpdated::class, function (VoucherUpdated $event) {
            // configure transport
        });
    }

    // ecommerce.sales.voucher.voucher-channel.updated
    protected function onVoucherVoucherChannelUpdated(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(VoucherVoucherChannelUpdated::class, function (VoucherVoucherChannelUpdated $event) {
            // configure transport
        });
    }

    // ecommerce.sales.voucher.voucher-channel.removed
    protected function onVoucherVoucherChannelRemoved(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(VoucherVoucherChannelRemoved::class, function (VoucherVoucherChannelRemoved $event) {
            // configure transport
        });
    }

    // ecommerce.sales.voucher.channel-hook.updated
    protected function onVoucherChannelHookUpdated(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(VoucherChannelHookUpdated::class, function (VoucherChannelHookUpdated $event) {
            // configure transport
        });
    }

    // ecommerce.sales.voucher.channel-hook.removed
    protected function onVoucherChannelHookRemoved(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(VoucherChannelHookRemoved::class, function (VoucherChannelHookRemoved $event) {
            // configure transport
        });
    }
}

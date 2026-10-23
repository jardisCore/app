<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command\Validation;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Voucher\Command\ChannelHookOfSetVoucherChannel;
use Ecommerce\Sales\Model\Voucher\Command\SetVoucherChannel;
use Ecommerce\Sales\Model\Voucher\Command\Validation\ChannelHookOfSetVoucherChannelValidator;
use Ecommerce\Sales\Model\Voucher\Command\Validation\VoucherChannelSetValidator;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;

/**
 * Validator registry of the CommandSetVoucherChannel command DTO graph.
 *
 * One validator per DTO class of the graph (root + nested ONE + nested MANY); the
 * door's input check validates the whole tree with it.
 */
class SetVoucherChannelValidatorRegistry extends EcommerceContext
{
    /**
     * Builds the registry.
     *
     * @return ValidatorRegistry
     * @throws Throwable
     */
    public function __invoke(): ValidatorRegistry
    {
        $registry = new ValidatorRegistry();
        $registry->register(SetVoucherChannel::class, ($this->handle(VoucherChannelSetValidator::class))());
        $registry->register(
            ChannelHookOfSetVoucherChannel::class,
            ($this->handle(ChannelHookOfSetVoucherChannelValidator::class))()
        );

        return $registry;
    }
}

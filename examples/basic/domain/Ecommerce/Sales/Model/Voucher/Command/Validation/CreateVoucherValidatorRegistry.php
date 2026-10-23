<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command\Validation;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Voucher\Command\ChannelHook;
use Ecommerce\Sales\Model\Voucher\Command\Validation\ChannelHookValidator;
use Ecommerce\Sales\Model\Voucher\Command\Validation\VoucherChannelValidator;
use Ecommerce\Sales\Model\Voucher\Command\Validation\VoucherValidator;
use Ecommerce\Sales\Model\Voucher\Command\Voucher;
use Ecommerce\Sales\Model\Voucher\Command\VoucherChannel;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;

/**
 * Validator registry of the CommandVoucher command DTO graph.
 *
 * One validator per DTO class of the graph (root + nested ONE + nested MANY); the
 * door's input check validates the whole tree with it.
 */
class CreateVoucherValidatorRegistry extends EcommerceContext
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
        $registry->register(Voucher::class, ($this->handle(VoucherValidator::class))());
        $registry->register(VoucherChannel::class, ($this->handle(VoucherChannelValidator::class))());
        $registry->register(ChannelHook::class, ($this->handle(ChannelHookValidator::class))());

        return $registry;
    }
}

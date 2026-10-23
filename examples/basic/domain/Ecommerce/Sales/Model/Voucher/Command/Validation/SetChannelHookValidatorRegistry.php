<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command\Validation;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Voucher\Command\SetChannelHook;
use Ecommerce\Sales\Model\Voucher\Command\Validation\ChannelHookSetValidator;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;

/**
 * Validator registry of the CommandSetChannelHook command DTO graph.
 *
 * One validator per DTO class of the graph (root + nested ONE + nested MANY); the
 * door's input check validates the whole tree with it.
 */
class SetChannelHookValidatorRegistry extends EcommerceContext
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
        $registry->register(SetChannelHook::class, ($this->handle(ChannelHookSetValidator::class))());

        return $registry;
    }
}

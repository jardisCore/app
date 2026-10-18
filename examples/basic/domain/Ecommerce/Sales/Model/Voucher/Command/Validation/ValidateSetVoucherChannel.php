<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Command\Validation;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Voucher\Command\ChannelHookOfSetVoucherChannel;
use Ecommerce\Sales\Model\Voucher\Command\SetVoucherChannel;
use Ecommerce\Sales\Model\Voucher\Command\Validation\ChannelHookOfSetVoucherChannelValidator;
use Ecommerce\Sales\Model\Voucher\Command\Validation\VoucherChannelSetValidator;
use JardisSupport\Validation\ObjectValidator;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;
use Ecommerce\Sales\Model\Voucher\Command\SetVoucherChannel as CommandSetVoucherChannel;

/**
 * Validates the CommandSetVoucherChannel command DTO graph.
 *
 * Sets up ObjectValidator with ValidatorRegistry for recursive validation
 * of the complete DTO tree (root + nested ONE + nested MANY).
 */
class ValidateSetVoucherChannel extends EcommerceContext
{
    /**
     * Validates the command DTO.
     *
     * @param CommandSetVoucherChannel $dto Command DTO
     * @throws ValidationException If validation fails
     * @throws Throwable
     */
    public function __invoke(CommandSetVoucherChannel $dto): void
    {
        $registry = new ValidatorRegistry();
        $registry->register(SetVoucherChannel::class, ($this->handle(VoucherChannelSetValidator::class))());
        $registry->register(
            ChannelHookOfSetVoucherChannel::class,
            ($this->handle(ChannelHookOfSetVoucherChannelValidator::class))()
        );

        $validator = new ObjectValidator($registry, indexedLists: true);
        $result = $validator->validate($dto);
        $errors = $this->filterEmptyErrors($result->getErrors());
        $kinds = $this->filterEmptyErrors($result->getKinds());

        if (!empty($errors)) {
            throw new ValidationException($errors, $kinds);
        }
    }

    /**
     * Recursively filters empty error arrays from validation results.
     *
     * @param array<string, mixed> $errors
     * @return array<string, mixed>
     */
    private function filterEmptyErrors(array $errors): array
    {
        $filtered = [];

        foreach ($errors as $key => $value) {
            if (is_array($value)) {
                $nested = $this->filterEmptyErrors($value);
                if (!empty($nested)) {
                    $filtered[$key] = $nested;
                }
            } else {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }
}

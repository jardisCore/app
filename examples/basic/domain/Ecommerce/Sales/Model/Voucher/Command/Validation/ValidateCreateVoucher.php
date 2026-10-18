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
use JardisSupport\Validation\ObjectValidator;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;
use Ecommerce\Sales\Model\Voucher\Command\Voucher as CommandVoucher;

/**
 * Validates the CommandVoucher command DTO graph.
 *
 * Sets up ObjectValidator with ValidatorRegistry for recursive validation
 * of the complete DTO tree (root + nested ONE + nested MANY).
 */
class ValidateCreateVoucher extends EcommerceContext
{
    /**
     * Validates the command DTO.
     *
     * @param CommandVoucher $dto Command DTO
     * @throws ValidationException If validation fails
     * @throws Throwable
     */
    public function __invoke(CommandVoucher $dto): void
    {
        $registry = new ValidatorRegistry();
        $registry->register(Voucher::class, ($this->handle(VoucherValidator::class))());
        $registry->register(VoucherChannel::class, ($this->handle(VoucherChannelValidator::class))());
        $registry->register(ChannelHook::class, ($this->handle(ChannelHookValidator::class))());

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

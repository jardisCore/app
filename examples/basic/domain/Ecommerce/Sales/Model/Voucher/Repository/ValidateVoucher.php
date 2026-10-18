<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Voucher\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Entity\Validation\ChannelHookValidator;
use Ecommerce\Sales\Entity\Validation\VoucherChannelValidator;
use Ecommerce\Sales\Entity\Validation\VoucherValidator;
use Ecommerce\Sales\Model\Voucher\Command\Validation\ValidationException;
use JardisSupport\Validation\CompositeFieldValidator;
use Throwable;
use Ecommerce\Sales\Model\Voucher\Aggregate\Voucher as VoucherHandler;

/**
 * Validates Voucher aggregate entities before persistence.
 *
 * Validates all entities using their generated validators.
 * Called automatically by Repository before PersistAggregate.
 */
class ValidateVoucher extends EcommerceContext
{
    /**
     * Validates all entities in the aggregate.
     *
     * @param VoucherHandler $handler Aggregate handler
     * @throws ValidationException If validation fails
     * @throws Throwable
     */
    public function __invoke(VoucherHandler $handler): void
    {
        if ($handler->isMarkedForDeletion()) {
            return;
        }

        $errors = [];
        $kinds = [];
        $aggregate = $handler->getData();

        if (!empty($handler->getEntityData('voucher')['values'])) {
            $this->validateEntity($aggregate, VoucherValidator::class, 'voucher', $errors, $kinds);
        }

        $voucherChannel = $aggregate->getVoucherChannel();
        if ($voucherChannel !== null && !empty($handler->getEntityData('voucherChannel')['values'])) {
            $this->validateEntity($voucherChannel, VoucherChannelValidator::class, 'voucherChannel', $errors, $kinds);
        }
        if ($voucherChannel !== null) {
            $channelHook = $voucherChannel->getChannelHook();
            if ($channelHook !== null && !empty($handler->getEntityData('channelHook')['values'])) {
                $this->validateEntity($channelHook, ChannelHookValidator::class, 'channelHook', $errors, $kinds);
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors, $kinds, root: 'voucher');
        }
    }

    /**
     * Validates a single entity against its validator.
     *
     * @param object $entity The entity to validate
     * @param class-string $validatorClass Validator class name
     * @param string $entityName Entity identifier for error grouping
     * @param array<string, mixed> $errors Collected errors (by reference)
     * @param array<string, mixed> $kinds Collected validator kinds, same shape (by reference)
     * @throws Throwable
     */
    protected function validateEntity(
        object $entity,
        string $validatorClass,
        string $entityName,
        array &$errors,
        array &$kinds
    ): void {
        $validatorInstance = $this->handle($validatorClass);
        /** @var callable(): CompositeFieldValidator $validatorInstance */
        $validator = $validatorInstance();
        $result = $validator->validate($entity);

        if (!$result->isValid()) {
            $errors[$entityName] = $result->getErrors();
            $kinds[$entityName] = $result->getKinds();
        }
    }
}

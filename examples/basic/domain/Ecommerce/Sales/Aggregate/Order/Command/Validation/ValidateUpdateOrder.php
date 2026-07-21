<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Command\Validation;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Order\Command\UpdateOrder;
use Ecommerce\Sales\Aggregate\Order\Command\Validation\OrderValidator;
use JardisSupport\Validation\ObjectValidator;
use JardisSupport\Validation\ValidatorRegistry;
use JsonException;
use Throwable;
use Ecommerce\Sales\Aggregate\Order\Command\UpdateOrder as CommandUpdateOrder;

/**
 * Validates the CommandUpdateOrder command DTO graph.
 *
 * Sets up ObjectValidator with ValidatorRegistry for recursive validation
 * of the complete DTO tree (root + nested ONE + nested MANY).
 */
class ValidateUpdateOrder extends EcommerceContext
{
    /**
     * Validates the command DTO.
     *
     * @param CommandUpdateOrder $dto Command DTO
     * @throws ValidationException If validation fails
     * @throws JsonException
     * @throws Throwable
     */
    public function __invoke(CommandUpdateOrder $dto): void
    {
        $registry = new ValidatorRegistry();
        $registry->register(UpdateOrder::class, ($this->handle(OrderValidator::class))());

        $validator = new ObjectValidator($registry);
        $result = $validator->validate($dto);
        $errors = $this->filterEmptyErrors($result->getErrors());

        if (!empty($errors)) {
            throw new ValidationException(json_encode($errors, JSON_THROW_ON_ERROR));
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

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Command\Validation;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Invoice\Command\AddInvoiceLine;
use Ecommerce\Sales\Model\Invoice\Command\Validation\InvoiceLineValidator;
use JardisSupport\Validation\ObjectValidator;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;
use Ecommerce\Sales\Model\Invoice\Command\AddInvoiceLine as CommandAddInvoiceLine;

/**
 * Validates the CommandAddInvoiceLine command DTO graph.
 *
 * Sets up ObjectValidator with ValidatorRegistry for recursive validation
 * of the complete DTO tree (root + nested ONE + nested MANY).
 */
class ValidateAddInvoiceLine extends EcommerceContext
{
    /**
     * Validates the command DTO.
     *
     * @param CommandAddInvoiceLine $dto Command DTO
     * @throws ValidationException If validation fails
     * @throws Throwable
     */
    public function __invoke(CommandAddInvoiceLine $dto): void
    {
        $registry = new ValidatorRegistry();
        $registry->register(AddInvoiceLine::class, ($this->handle(InvoiceLineValidator::class))());

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

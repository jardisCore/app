<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Command\Validation;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Invoice\Command\Invoice;
use Ecommerce\Sales\Model\Invoice\Command\InvoiceLine;
use Ecommerce\Sales\Model\Invoice\Command\Validation\InvoiceLineValidator;
use Ecommerce\Sales\Model\Invoice\Command\Validation\InvoiceValidator;
use JardisSupport\Validation\ObjectValidator;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;
use Ecommerce\Sales\Model\Invoice\Command\Invoice as CommandInvoice;

/**
 * Validates the CommandInvoice command DTO graph.
 *
 * Sets up ObjectValidator with ValidatorRegistry for recursive validation
 * of the complete DTO tree (root + nested ONE + nested MANY).
 */
class ValidateCreateInvoice extends EcommerceContext
{
    /**
     * Validates the command DTO.
     *
     * @param CommandInvoice $dto Command DTO
     * @throws ValidationException If validation fails
     * @throws Throwable
     */
    public function __invoke(CommandInvoice $dto): void
    {
        $registry = new ValidatorRegistry();
        $registry->register(Invoice::class, ($this->handle(InvoiceValidator::class))());
        $registry->register(InvoiceLine::class, ($this->handle(InvoiceLineValidator::class))());

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

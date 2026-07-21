<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Repository;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Invoice\Command\Validation\ValidationException;
use Ecommerce\Sales\Entity\Validation\InvoiceLineValidator;
use Ecommerce\Sales\Entity\Validation\InvoiceValidator;
use JsonException;
use Throwable;
use Ecommerce\Sales\Aggregate\Invoice\Aggregate\Invoice as InvoiceHandler;

/**
 * Validates Invoice aggregate entities before persistence.
 *
 * Validates all entities using their generated validators.
 * Called automatically by Repository before PersistAggregate.
 */
class ValidateInvoice extends EcommerceContext
{
    /**
     * Validates all entities in the aggregate.
     *
     * @param InvoiceHandler $handler Aggregate handler
     * @throws ValidationException If validation fails
     * @throws JsonException
     * @throws Throwable
     */
    public function __invoke(InvoiceHandler $handler): void
    {
        if ($handler->isMarkedForDeletion()) {
            return;
        }

        $errors = [];
        $aggregate = $handler->getData();

        if (!empty($handler->getEntityData('invoice')['values'])) {
            $this->validateEntity($aggregate, InvoiceValidator::class, 'invoice', $errors);
        }

        $invoiceLineCollection = $handler->getCollectionData('invoiceLine');
        foreach ($aggregate->getInvoiceLine() as $index => $invoiceLine) {
            if (!empty($invoiceLineCollection[$index]['values'])) {
                $this->validateEntity($invoiceLine, InvoiceLineValidator::class, "invoiceLine[{$index}]", $errors);
            }
        }

        if (!empty($errors)) {
            throw new ValidationException(json_encode($errors, JSON_THROW_ON_ERROR));
        }
    }

    /**
     * Validates a single entity against its validator.
     *
     * @param object $entity The entity to validate
     * @param class-string $validatorClass Validator class name
     * @param string $entityName Entity identifier for error grouping
     * @param array<string, mixed> $errors Collected errors (by reference)
     * @throws Throwable
     */
    protected function validateEntity(
        object $entity,
        string $validatorClass,
        string $entityName,
        array &$errors
    ): void {
        $validator = ($this->handle($validatorClass))();
        $result = $validator->validate($entity);

        if (!$result->isValid()) {
            $errors[$entityName] = $result->getErrors();
        }
    }
}

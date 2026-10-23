<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Command\Validation;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Invoice\Command\Invoice;
use Ecommerce\Sales\Model\Invoice\Command\InvoiceLine;
use Ecommerce\Sales\Model\Invoice\Command\Validation\InvoiceLineValidator;
use Ecommerce\Sales\Model\Invoice\Command\Validation\InvoiceValidator;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;

/**
 * Validator registry of the CommandInvoice command DTO graph.
 *
 * One validator per DTO class of the graph (root + nested ONE + nested MANY); the
 * door's input check validates the whole tree with it.
 */
class CreateInvoiceValidatorRegistry extends EcommerceContext
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
        $registry->register(Invoice::class, ($this->handle(InvoiceValidator::class))());
        $registry->register(InvoiceLine::class, ($this->handle(InvoiceLineValidator::class))());

        return $registry;
    }
}

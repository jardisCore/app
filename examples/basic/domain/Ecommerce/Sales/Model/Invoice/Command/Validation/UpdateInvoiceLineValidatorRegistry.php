<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice\Command\Validation;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Invoice\Command\UpdateInvoiceLine;
use Ecommerce\Sales\Model\Invoice\Command\Validation\InvoiceLineValidator;
use JardisSupport\Validation\ValidatorRegistry;
use Throwable;

/**
 * Validator registry of the CommandUpdateInvoiceLine command DTO graph.
 *
 * One validator per DTO class of the graph (root + nested ONE + nested MANY); the
 * door's input check validates the whole tree with it.
 */
class UpdateInvoiceLineValidatorRegistry extends EcommerceContext
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
        $registry->register(UpdateInvoiceLine::class, ($this->handle(InvoiceLineValidator::class))());

        return $registry;
    }
}

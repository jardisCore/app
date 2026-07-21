<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Aggregate\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Invoice\Aggregate\Entity\InvoiceLine;
use JardisSupport\Data\Hydration;
use ReflectionException;
use Throwable;
use Ecommerce\Sales\Aggregate\Invoice\Aggregate\Entity\Invoice as InvoiceAggregate;

/**
 * Action: AddInvoiceLine
 */
class AddInvoiceLine extends EcommerceContext
{
    /**
     * Adds a InvoiceLine to the invoiceLine collection.
     *
     * @param InvoiceAggregate $aggregate
     * @param array<string, mixed> $data Entity data
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __invoke(InvoiceAggregate $aggregate, array $data): void
    {
        $hydration = $this->handle(Hydration::class);

        $entity = $hydration->hydrate($this->handle(InvoiceLine::class), $data);

        $aggregate->addInvoiceLine($entity);
    }
}

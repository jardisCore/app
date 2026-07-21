<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Aggregate\Invoice\Aggregate\Invoice;
use Ecommerce\Sales\FieldMap;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Sales\Aggregate\Invoice\Command\Invoice as CommandInvoice;

/**
 * Action: HydrateCreateInvoiceEntities
 */
class HydrateCreateInvoiceEntities extends EcommerceContext
{
    /**
     * Hydrates all entities from the command DTO.
     *
     * @param Invoice $handler Aggregate handler
     * @param CommandInvoice $invoice Command data
     * @throws Throwable
     */
    public function __invoke(Invoice $handler, CommandInvoice $invoice): void
    {
        $this->hydrateInvoice($handler, $invoice);
        $this->hydrateInvoiceLine($handler, $invoice);
    }

    /**
     * Hydrates Invoice entity data.
     * @throws Throwable
     */
    protected function hydrateInvoice(Invoice $handler, CommandInvoice $invoice): void
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($invoice);

        $handler->setInvoice($this->handle(FieldMapper::class)->toColumns(array_filter(
            $rawData,
            fn($key) => !in_array($key, ["invoiceLine"], true),
            ARRAY_FILTER_USE_KEY
        ), $fieldMap->invoicesColumns()));
    }

    /**
     * Hydrates InvoiceLine collection.
     * @throws Throwable
     */
    protected function hydrateInvoiceLine(Invoice $handler, CommandInvoice $invoice): void
    {
        $fieldMap = $this->handle(FieldMap::class);

        // InvoiceLine collection
        foreach ($invoice->invoiceLine as $invoiceLineDto) {
            $handler->addInvoiceLine($this->handle(FieldMapper::class)->toColumns(get_object_vars($invoiceLineDto), $fieldMap->invoiceLinesColumns()));
        }
    }
}

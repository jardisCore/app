<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Model\Invoice;

use Ecommerce\EcommerceContext;
use Ecommerce\Sales\Model\Invoice\Command\Handler\AddInvoiceLine;
use Ecommerce\Sales\Model\Invoice\Command\Handler\CreateInvoice;
use Ecommerce\Sales\Model\Invoice\Command\Handler\RemoveInvoice;
use Ecommerce\Sales\Model\Invoice\Command\Handler\RemoveInvoiceLine;
use Ecommerce\Sales\Model\Invoice\Command\Handler\UpdateInvoice;
use Ecommerce\Sales\Model\Invoice\Command\Handler\UpdateInvoiceLine;
use Ecommerce\Sales\Model\Invoice\Event\InvoiceEvents;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use Throwable;
use Ecommerce\Sales\Model\Invoice\Command\AddInvoiceLine as CommandAddInvoiceLine;
use Ecommerce\Sales\Model\Invoice\Command\Invoice as CommandInvoice;
use Ecommerce\Sales\Model\Invoice\Command\RemoveInvoice as CommandRemoveInvoice;
use Ecommerce\Sales\Model\Invoice\Command\RemoveInvoiceLine as CommandRemoveInvoiceLine;
use Ecommerce\Sales\Model\Invoice\Command\UpdateInvoice as CommandUpdateInvoice;
use Ecommerce\Sales\Model\Invoice\Command\UpdateInvoiceLine as CommandUpdateInvoiceLine;

/**
 * Invoice Aggregate Facade.
 *
 * Hosts the inline command operations for this aggregate (writes).
 * Domain events are exposed via event() (constants on InvoiceEvents).
 * Query/list operations live on the sibling read facade.
 */
class Invoice extends EcommerceContext
{
    /**
     * Creates a new Invoice.
     *
     * @param CommandInvoice $invoice
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function createInvoice(CommandInvoice $invoice, string $version = ''): DomainResponseInterface
    {
        return $this->context(CreateInvoice::class, $invoice, $version)();
    }

    /**
     * Update Invoice operation.
     *
     * @param CommandUpdateInvoice $updateInvoice
     * @param string $version Optional ClassVersion override (default: caller version)
     * @param array<string, mixed> $expected Optional read basis (CAS): outer field name => value the caller read
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function updateInvoice(
        CommandUpdateInvoice $updateInvoice,
        string $version = '',
        array $expected = []
    ): DomainResponseInterface {
        return $this->context(UpdateInvoice::class, $updateInvoice, $version)($expected);
    }

    /**
     * Add InvoiceLine operation.
     *
     * @param CommandAddInvoiceLine $addInvoiceLine
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function addInvoiceLine(CommandAddInvoiceLine $addInvoiceLine, string $version = ''): DomainResponseInterface
    {
        return $this->context(AddInvoiceLine::class, $addInvoiceLine, $version)();
    }

    /**
     * Remove InvoiceLine operation.
     *
     * @param CommandRemoveInvoiceLine $removeInvoiceLine
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function removeInvoiceLine(
        CommandRemoveInvoiceLine $removeInvoiceLine,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(RemoveInvoiceLine::class, $removeInvoiceLine, $version)();
    }

    /**
     * Update InvoiceLine operation.
     *
     * @param CommandUpdateInvoiceLine $updateInvoiceLine
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function updateInvoiceLine(
        CommandUpdateInvoiceLine $updateInvoiceLine,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(UpdateInvoiceLine::class, $updateInvoiceLine, $version)();
    }

    /**
     * Removes Invoice aggregate.
     *
     * @param CommandRemoveInvoice $removeInvoice
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function removeInvoice(CommandRemoveInvoice $removeInvoice, string $version = ''): DomainResponseInterface
    {
        return $this->context(RemoveInvoice::class, $removeInvoice, $version)();
    }

    /**
     * Returns the Event registry.
     *
     * @return InvoiceEvents
     * @throws Throwable
     */
    public function event(): InvoiceEvents
    {
        return $this->handle(InvoiceEvents::class);
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Invoice\Event;

use JardisSupport\Contract\EventListener\EventListenerRegistryInterface;

/**
 * Event routing for the Invoice aggregate.
 *
 * Configure how domain events are transported to consumers.
 * Each event is registered with an empty listener — fill in the transport logic.
 *
 * Channel keys:
 *   ecommerce.sales.invoice.created
 *   ecommerce.sales.invoice.removed
 *   ecommerce.sales.invoice.updated
 *   ecommerce.sales.invoice.invoice-line.added
 *   ecommerce.sales.invoice.invoice-line.removed
 */
class InvoiceEventRouter
{
    public function __invoke(EventListenerRegistryInterface $registry): void
    {
        $this->onInvoiceCreated($registry);
        $this->onInvoiceRemoved($registry);
        $this->onInvoiceUpdated($registry);
        $this->onInvoiceInvoiceLineAdded($registry);
        $this->onInvoiceInvoiceLineRemoved($registry);
    }

    // ecommerce.sales.invoice.created
    protected function onInvoiceCreated(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(InvoiceCreated::class, function (InvoiceCreated $event) {
            // configure transport
        });
    }

    // ecommerce.sales.invoice.removed
    protected function onInvoiceRemoved(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(InvoiceRemoved::class, function (InvoiceRemoved $event) {
            // configure transport
        });
    }

    // ecommerce.sales.invoice.updated
    protected function onInvoiceUpdated(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(InvoiceUpdated::class, function (InvoiceUpdated $event) {
            // configure transport
        });
    }

    // ecommerce.sales.invoice.invoice-line.added
    protected function onInvoiceInvoiceLineAdded(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(InvoiceInvoiceLineAdded::class, function (InvoiceInvoiceLineAdded $event) {
            // configure transport
        });
    }

    // ecommerce.sales.invoice.invoice-line.removed
    protected function onInvoiceInvoiceLineRemoved(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(InvoiceInvoiceLineRemoved::class, function (InvoiceInvoiceLineRemoved $event) {
            // configure transport
        });
    }
}

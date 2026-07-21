<?php

declare(strict_types=1);

namespace Ecommerce;

use JardisSupport\Contract\Kernel\DomainKernelInterface;
use Ecommerce\Catalog\Catalog;
use Ecommerce\Fulfillment\Fulfillment;
use Ecommerce\Sales\Sales;
use Ecommerce\Catalog\Aggregate\Category\Event\CategoryEventRouter;
use Ecommerce\Catalog\Aggregate\Product\Event\ProductEventRouter;
use Ecommerce\Fulfillment\Aggregate\Shipment\Event\ShipmentEventRouter;
use Ecommerce\Sales\Aggregate\Invoice\Event\InvoiceEventRouter;
use Ecommerce\Sales\Aggregate\Order\Event\OrderEventRouter;

/**
 * Ecommerce Domain.
 *
 * Provides access to bounded contexts.
 *
 * Usage:
 *   $domain->catalog()->{aggregate}()->{useCase}(...)
 *   $domain->fulfillment()->{aggregate}()->{useCase}(...)
 *   $domain->sales()->{aggregate}()->{useCase}(...)
 */
final class Ecommerce
{
    private DomainKernelInterface $kernel;

    public function __construct(DomainKernelInterface $kernel)
    {
        $this->kernel = $kernel;

        $registry = $kernel->eventListenerRegistry();
        if ($registry !== null) {
            (new CategoryEventRouter())($registry);
            (new ProductEventRouter())($registry);
            (new ShipmentEventRouter())($registry);
            (new InvoiceEventRouter())($registry);
            (new OrderEventRouter())($registry);
        }
    }

    /**
     * Returns the Catalog bounded context.
     *
     * @return Catalog
     */
    public function catalog(): Catalog
    {
        return new Catalog($this->kernel);
    }

    /**
     * Returns the Fulfillment bounded context.
     *
     * @return Fulfillment
     */
    public function fulfillment(): Fulfillment
    {
        return new Fulfillment($this->kernel);
    }

    /**
     * Returns the Sales bounded context.
     *
     * @return Sales
     */
    public function sales(): Sales
    {
        return new Sales($this->kernel);
    }
}

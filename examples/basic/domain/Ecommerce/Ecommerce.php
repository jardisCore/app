<?php

declare(strict_types=1);

namespace Ecommerce;

use JardisSupport\Contract\Kernel\DomainKernelInterface;
use Ecommerce\Catalog\Catalog;
use Ecommerce\Fulfillment\Fulfillment;
use Ecommerce\Sales\Sales;
use Ecommerce\Catalog\Model\Category\Event\CategoryEventRouter;
use Ecommerce\Catalog\Model\Product\Event\ProductEventRouter;
use Ecommerce\Fulfillment\Model\Shipment\Event\ShipmentEventRouter;
use Ecommerce\Sales\Model\Invoice\Event\InvoiceEventRouter;
use Ecommerce\Sales\Model\Order\Event\OrderEventRouter;
use Ecommerce\Sales\Model\PriceTier\Event\PriceTierEventRouter;
use Ecommerce\Sales\Model\Voucher\Event\VoucherEventRouter;

/**
 * Ecommerce Domain.
 *
 * Provides access to bounded contexts.
 *
 * Usage:
 *   $domain->catalog($actor)->{aggregate}()->{useCase}(...)
 *   $domain->fulfillment($actor)->{aggregate}()->{useCase}(...)
 *   $domain->sales($actor)->{aggregate}()->{useCase}(...)
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
            (new PriceTierEventRouter())($registry);
            (new VoucherEventRouter())($registry);
        }
    }

    /**
     * Returns the Catalog bounded context.
     *
     *
     * The actor is the value of the run (V1): routes.php builds it per request, a CLI, queue or cron
     * entry passes system(name). The bounded context carries it to every class it starts.
     *
     * @return Catalog
     */
    public function catalog(EcommerceActor $actor): Catalog
    {
        return new Catalog($this->kernel, null, '', $actor);
    }

    /**
     * Returns the Fulfillment bounded context.
     *
     *
     * The actor is the value of the run (V1): routes.php builds it per request, a CLI, queue or cron
     * entry passes system(name). The bounded context carries it to every class it starts.
     *
     * @return Fulfillment
     */
    public function fulfillment(EcommerceActor $actor): Fulfillment
    {
        return new Fulfillment($this->kernel, null, '', $actor);
    }

    /**
     * Returns the Sales bounded context.
     *
     *
     * The actor is the value of the run (V1): routes.php builds it per request, a CLI, queue or cron
     * entry passes system(name). The bounded context carries it to every class it starts.
     *
     * @return Sales
     */
    public function sales(EcommerceActor $actor): Sales
    {
        return new Sales($this->kernel, null, '', $actor);
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Sales\Aggregate\Order\Event;

use JardisSupport\Contract\EventListener\EventListenerRegistryInterface;

/**
 * Event routing for the Order aggregate.
 *
 * Configure how domain events are transported to consumers.
 * Each event is registered with an empty listener — fill in the transport logic.
 *
 * Channel keys:
 *   ecommerce.sales.order.created
 *   ecommerce.sales.order.removed
 *   ecommerce.sales.order.updated
 *   ecommerce.sales.order.customer.updated
 *   ecommerce.sales.order.address.updated
 *   ecommerce.sales.order.address.removed
 *   ecommerce.sales.order.order-item.added
 *   ecommerce.sales.order.order-item.removed
 *   ecommerce.sales.order.item-discount.added
 *   ecommerce.sales.order.item-discount.removed
 */
class OrderEventRouter
{
    public function __invoke(EventListenerRegistryInterface $registry): void
    {
        $this->onOrderCreated($registry);
        $this->onOrderRemoved($registry);
        $this->onOrderUpdated($registry);
        $this->onOrderCustomerUpdated($registry);
        $this->onOrderAddressUpdated($registry);
        $this->onOrderAddressRemoved($registry);
        $this->onOrderOrderItemAdded($registry);
        $this->onOrderOrderItemRemoved($registry);
        $this->onOrderItemDiscountAdded($registry);
        $this->onOrderItemDiscountRemoved($registry);
    }

    // ecommerce.sales.order.created
    protected function onOrderCreated(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(OrderCreated::class, function (OrderCreated $event) {
            // configure transport
        });
    }

    // ecommerce.sales.order.removed
    protected function onOrderRemoved(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(OrderRemoved::class, function (OrderRemoved $event) {
            // configure transport
        });
    }

    // ecommerce.sales.order.updated
    protected function onOrderUpdated(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(OrderUpdated::class, function (OrderUpdated $event) {
            // configure transport
        });
    }

    // ecommerce.sales.order.customer.updated
    protected function onOrderCustomerUpdated(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(OrderCustomerUpdated::class, function (OrderCustomerUpdated $event) {
            // configure transport
        });
    }

    // ecommerce.sales.order.address.updated
    protected function onOrderAddressUpdated(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(OrderAddressUpdated::class, function (OrderAddressUpdated $event) {
            // configure transport
        });
    }

    // ecommerce.sales.order.address.removed
    protected function onOrderAddressRemoved(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(OrderAddressRemoved::class, function (OrderAddressRemoved $event) {
            // configure transport
        });
    }

    // ecommerce.sales.order.order-item.added
    protected function onOrderOrderItemAdded(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(OrderOrderItemAdded::class, function (OrderOrderItemAdded $event) {
            // configure transport
        });
    }

    // ecommerce.sales.order.order-item.removed
    protected function onOrderOrderItemRemoved(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(OrderOrderItemRemoved::class, function (OrderOrderItemRemoved $event) {
            // configure transport
        });
    }

    // ecommerce.sales.order.item-discount.added
    protected function onOrderItemDiscountAdded(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(OrderItemDiscountAdded::class, function (OrderItemDiscountAdded $event) {
            // configure transport
        });
    }

    // ecommerce.sales.order.item-discount.removed
    protected function onOrderItemDiscountRemoved(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(OrderItemDiscountRemoved::class, function (OrderItemDiscountRemoved $event) {
            // configure transport
        });
    }
}

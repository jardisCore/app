<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Event;

use JardisSupport\Contract\EventListener\EventListenerRegistryInterface;

/**
 * Event routing for the Shipment aggregate.
 *
 * Configure how domain events are transported to consumers.
 * Each event is registered with an empty listener — fill in the transport logic.
 *
 * Channel keys:
 *   ecommerce.fulfillment.shipment.created
 *   ecommerce.fulfillment.shipment.removed
 *   ecommerce.fulfillment.shipment.updated
 *   ecommerce.fulfillment.shipment.shipment-address.updated
 *   ecommerce.fulfillment.shipment.shipment-item.added
 *   ecommerce.fulfillment.shipment.shipment-item.removed
 *   ecommerce.fulfillment.shipment.tracking-event.added
 *   ecommerce.fulfillment.shipment.tracking-event.removed
 */
class ShipmentEventRouter
{
    public function __invoke(EventListenerRegistryInterface $registry): void
    {
        $this->onShipmentCreated($registry);
        $this->onShipmentRemoved($registry);
        $this->onShipmentUpdated($registry);
        $this->onShipmentShipmentAddressUpdated($registry);
        $this->onShipmentShipmentItemAdded($registry);
        $this->onShipmentShipmentItemRemoved($registry);
        $this->onShipmentTrackingEventAdded($registry);
        $this->onShipmentTrackingEventRemoved($registry);
    }

    // ecommerce.fulfillment.shipment.created
    protected function onShipmentCreated(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(ShipmentCreated::class, function (ShipmentCreated $event) {
            // configure transport
        });
    }

    // ecommerce.fulfillment.shipment.removed
    protected function onShipmentRemoved(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(ShipmentRemoved::class, function (ShipmentRemoved $event) {
            // configure transport
        });
    }

    // ecommerce.fulfillment.shipment.updated
    protected function onShipmentUpdated(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(ShipmentUpdated::class, function (ShipmentUpdated $event) {
            // configure transport
        });
    }

    // ecommerce.fulfillment.shipment.shipment-address.updated
    protected function onShipmentShipmentAddressUpdated(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(ShipmentShipmentAddressUpdated::class, function (ShipmentShipmentAddressUpdated $event) {
            // configure transport
        });
    }

    // ecommerce.fulfillment.shipment.shipment-item.added
    protected function onShipmentShipmentItemAdded(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(ShipmentShipmentItemAdded::class, function (ShipmentShipmentItemAdded $event) {
            // configure transport
        });
    }

    // ecommerce.fulfillment.shipment.shipment-item.removed
    protected function onShipmentShipmentItemRemoved(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(ShipmentShipmentItemRemoved::class, function (ShipmentShipmentItemRemoved $event) {
            // configure transport
        });
    }

    // ecommerce.fulfillment.shipment.tracking-event.added
    protected function onShipmentTrackingEventAdded(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(ShipmentTrackingEventAdded::class, function (ShipmentTrackingEventAdded $event) {
            // configure transport
        });
    }

    // ecommerce.fulfillment.shipment.tracking-event.removed
    protected function onShipmentTrackingEventRemoved(EventListenerRegistryInterface $registry): void
    {
        $registry->listen(ShipmentTrackingEventRemoved::class, function (ShipmentTrackingEventRemoved $event) {
            // configure transport
        });
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Model\Shipment;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\AddShipmentItem;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\AddTrackingEvent;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\CreateShipment;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\RemoveShipment;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\RemoveShipmentItem;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\RemoveTrackingEvent;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\SetShipmentAddress;
use Ecommerce\Fulfillment\Model\Shipment\Command\Handler\UpdateShipment;
use Ecommerce\Fulfillment\Model\Shipment\Event\ShipmentEvents;
use JardisSupport\Contract\Kernel\DomainResponseInterface;
use Throwable;
use Ecommerce\Fulfillment\Model\Shipment\Command\AddShipmentItem as CommandAddShipmentItem;
use Ecommerce\Fulfillment\Model\Shipment\Command\AddTrackingEvent as CommandAddTrackingEvent;
use Ecommerce\Fulfillment\Model\Shipment\Command\RemoveShipment as CommandRemoveShipment;
use Ecommerce\Fulfillment\Model\Shipment\Command\RemoveShipmentItem as CommandRemoveShipmentItem;
use Ecommerce\Fulfillment\Model\Shipment\Command\RemoveTrackingEvent as CommandRemoveTrackingEvent;
use Ecommerce\Fulfillment\Model\Shipment\Command\SetShipmentAddress as CommandSetShipmentAddress;
use Ecommerce\Fulfillment\Model\Shipment\Command\Shipment as CommandShipment;
use Ecommerce\Fulfillment\Model\Shipment\Command\UpdateShipment as CommandUpdateShipment;

/**
 * Shipment Aggregate Facade.
 *
 * Hosts the inline command operations for this aggregate (writes).
 * Domain events are exposed via event() (constants on ShipmentEvents).
 * Query/list operations live on the sibling read facade.
 */
class Shipment extends EcommerceContext
{
    /**
     * Creates a new Shipment.
     *
     * @param CommandShipment $shipment
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function createShipment(CommandShipment $shipment, string $version = ''): DomainResponseInterface
    {
        return $this->context(CreateShipment::class, $shipment, $version)();
    }

    /**
     * Update Shipment operation.
     *
     * @param CommandUpdateShipment $updateShipment
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function updateShipment(CommandUpdateShipment $updateShipment, string $version = ''): DomainResponseInterface
    {
        return $this->context(UpdateShipment::class, $updateShipment, $version)();
    }

    /**
     * Set ShipmentAddress operation.
     *
     * @param CommandSetShipmentAddress $setShipmentAddress
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function setShipmentAddress(
        CommandSetShipmentAddress $setShipmentAddress,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(SetShipmentAddress::class, $setShipmentAddress, $version)();
    }

    /**
     * Add ShipmentItem operation.
     *
     * @param CommandAddShipmentItem $addShipmentItem
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function addShipmentItem(
        CommandAddShipmentItem $addShipmentItem,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(AddShipmentItem::class, $addShipmentItem, $version)();
    }

    /**
     * Remove ShipmentItem operation.
     *
     * @param CommandRemoveShipmentItem $removeShipmentItem
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function removeShipmentItem(
        CommandRemoveShipmentItem $removeShipmentItem,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(RemoveShipmentItem::class, $removeShipmentItem, $version)();
    }

    /**
     * Add TrackingEvent operation.
     *
     * @param CommandAddTrackingEvent $addTrackingEvent
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function addTrackingEvent(
        CommandAddTrackingEvent $addTrackingEvent,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(AddTrackingEvent::class, $addTrackingEvent, $version)();
    }

    /**
     * Remove TrackingEvent operation.
     *
     * @param CommandRemoveTrackingEvent $removeTrackingEvent
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function removeTrackingEvent(
        CommandRemoveTrackingEvent $removeTrackingEvent,
        string $version = ''
    ): DomainResponseInterface {
        return $this->context(RemoveTrackingEvent::class, $removeTrackingEvent, $version)();
    }

    /**
     * Removes Shipment aggregate.
     *
     * @param CommandRemoveShipment $removeShipment
     * @param string $version Optional ClassVersion override (default: caller version)
     * @return DomainResponseInterface
     * @throws Throwable
     */
    public function removeShipment(CommandRemoveShipment $removeShipment, string $version = ''): DomainResponseInterface
    {
        return $this->context(RemoveShipment::class, $removeShipment, $version)();
    }

    /**
     * Returns the Event registry.
     *
     * @return ShipmentEvents
     * @throws Throwable
     */
    public function event(): ShipmentEvents
    {
        return $this->handle(ShipmentEvents::class);
    }
}

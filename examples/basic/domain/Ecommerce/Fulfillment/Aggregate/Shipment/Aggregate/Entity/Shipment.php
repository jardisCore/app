<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Entity;

use JardisSupport\Data\Attribute\Aggregate;
use JardisSupport\Data\Attribute\Relation;
use Ecommerce\Fulfillment\Entity\Shipment as EntityShipment;

/**
 * Root aggregate: Shipment
 *
 * Extends base entity with aggregate-specific relationships.
 */
#[Aggregate(name: 'Shipment', root: true)]
class Shipment extends EntityShipment
{
    #[Relation(type: 'one', target: ShipmentAddress::class)]
    private ?ShipmentAddress $shipmentAddress = null;

    /**
     * @var ShipmentItem[]
     */
    #[Relation(type: 'many', target: ShipmentItem::class)]
    private array $shipmentItem = [];

    /**
     * @var TrackingEvent[]
     */
    #[Relation(type: 'many', target: TrackingEvent::class)]
    private array $trackingEvent = [];

    public function getShipmentAddress(): ?ShipmentAddress
    {
        return $this->shipmentAddress;
    }

    /**
     * @return ShipmentItem[]
     */
    public function getShipmentItem(): array
    {
        return $this->shipmentItem;
    }

    /**
     * @return TrackingEvent[]
     */
    public function getTrackingEvent(): array
    {
        return $this->trackingEvent;
    }

    public function setShipmentAddress(?ShipmentAddress $shipmentAddress): self
    {
        $this->shipmentAddress = $shipmentAddress;
        return $this;
    }

    public function addShipmentItem(ShipmentItem $shipmentItem): self
    {
        $this->shipmentItem[] = $shipmentItem;
        return $this;
    }

    public function removeShipmentItem(ShipmentItem $shipmentItem): self
    {
        $this->shipmentItem = array_values(
            array_filter($this->shipmentItem, fn($existing) => $existing !== $shipmentItem)
        );
        return $this;
    }

    public function addTrackingEvent(TrackingEvent $trackingEvent): self
    {
        $this->trackingEvent[] = $trackingEvent;
        return $this;
    }

    public function removeTrackingEvent(TrackingEvent $trackingEvent): self
    {
        $this->trackingEvent = array_values(
            array_filter($this->trackingEvent, fn($existing) => $existing !== $trackingEvent)
        );
        return $this;
    }
}

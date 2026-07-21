<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Command\Handler\Action;

use Ecommerce\EcommerceContext;
use Ecommerce\Fulfillment\Aggregate\Shipment\Aggregate\Shipment;
use Ecommerce\Fulfillment\FieldMap;
use JardisSupport\Data\FieldMapper;
use Throwable;
use Ecommerce\Fulfillment\Aggregate\Shipment\Command\Shipment as CommandShipment;

/**
 * Action: HydrateCreateShipmentEntities
 */
class HydrateCreateShipmentEntities extends EcommerceContext
{
    /**
     * Hydrates all entities from the command DTO.
     *
     * @param Shipment $handler Aggregate handler
     * @param CommandShipment $shipment Command data
     * @throws Throwable
     */
    public function __invoke(Shipment $handler, CommandShipment $shipment): void
    {
        $this->hydrateShipment($handler, $shipment);
        $this->hydrateShipmentAddress($handler, $shipment);
        $this->hydrateShipmentItem($handler, $shipment);
        $this->hydrateTrackingEvent($handler, $shipment);
    }

    /**
     * Hydrates Shipment entity data.
     * @throws Throwable
     */
    protected function hydrateShipment(Shipment $handler, CommandShipment $shipment): void
    {
        $fieldMap = $this->handle(FieldMap::class);

        $rawData = get_object_vars($shipment);

        $handler->setShipment($this->handle(FieldMapper::class)->toColumns(array_filter(
            $rawData,
            fn($key) => !in_array($key, ["shipmentAddress","shipmentItem","trackingEvent"], true),
            ARRAY_FILTER_USE_KEY
        ), $fieldMap->shipmentsColumns()));
    }

    /**
     * Hydrates ShipmentAddress entity data.
     * @throws Throwable
     */
    protected function hydrateShipmentAddress(Shipment $handler, CommandShipment $shipment): void
    {
        $fieldMap = $this->handle(FieldMap::class);

        $handler->setShipmentAddress($this->handle(FieldMapper::class)->toColumns(get_object_vars($shipment->shipmentAddress), $fieldMap->shipmentAddressesColumns()));
    }

    /**
     * Hydrates ShipmentItem collection.
     * @throws Throwable
     */
    protected function hydrateShipmentItem(Shipment $handler, CommandShipment $shipment): void
    {
        $fieldMap = $this->handle(FieldMap::class);

        // ShipmentItem collection
        foreach ($shipment->shipmentItem as $shipmentItemDto) {
            $handler->addShipmentItem($this->handle(FieldMapper::class)->toColumns(get_object_vars($shipmentItemDto), $fieldMap->shipmentItemsColumns()));
        }
    }

    /**
     * Hydrates TrackingEvent collection.
     * @throws Throwable
     */
    protected function hydrateTrackingEvent(Shipment $handler, CommandShipment $shipment): void
    {
        $fieldMap = $this->handle(FieldMap::class);

        // TrackingEvent collection
        foreach ($shipment->trackingEvent as $trackingEventDto) {
            $handler->addTrackingEvent($this->handle(FieldMapper::class)->toColumns(get_object_vars($trackingEventDto), $fieldMap->trackingEventsColumns()));
        }
    }
}

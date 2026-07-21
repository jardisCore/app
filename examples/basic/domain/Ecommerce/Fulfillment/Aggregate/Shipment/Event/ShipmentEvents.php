<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Aggregate\Shipment\Event;

/**
 * Shipment Event Registry.
 *
 * Provides access to all Shipment domain events.
 * Use constants for event subscriptions and getAll() for bulk registration.
 */
class ShipmentEvents
{
    public const SHIPMENT_CREATED = ShipmentCreated::class;
    public const SHIPMENT_REMOVED = ShipmentRemoved::class;
    public const SHIPMENT_UPDATED = ShipmentUpdated::class;
    public const SHIPMENT_SHIPMENT_ADDRESS_UPDATED = ShipmentShipmentAddressUpdated::class;
    public const SHIPMENT_SHIPMENT_ITEM_ADDED = ShipmentShipmentItemAdded::class;
    public const SHIPMENT_SHIPMENT_ITEM_REMOVED = ShipmentShipmentItemRemoved::class;
    public const SHIPMENT_TRACKING_EVENT_ADDED = ShipmentTrackingEventAdded::class;
    public const SHIPMENT_TRACKING_EVENT_REMOVED = ShipmentTrackingEventRemoved::class;

    public static function getAll(): array
    {
        return [
            'ShipmentCreated' => self::SHIPMENT_CREATED,
            'ShipmentRemoved' => self::SHIPMENT_REMOVED,
            'ShipmentUpdated' => self::SHIPMENT_UPDATED,
            'ShipmentShipmentAddressUpdated' => self::SHIPMENT_SHIPMENT_ADDRESS_UPDATED,
            'ShipmentShipmentItemAdded' => self::SHIPMENT_SHIPMENT_ITEM_ADDED,
            'ShipmentShipmentItemRemoved' => self::SHIPMENT_SHIPMENT_ITEM_REMOVED,
            'ShipmentTrackingEventAdded' => self::SHIPMENT_TRACKING_EVENT_ADDED,
            'ShipmentTrackingEventRemoved' => self::SHIPMENT_TRACKING_EVENT_REMOVED,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Ecommerce\Fulfillment\Data;

/**
 * TrackingEventStatus enum
 *
 * Generated from database ENUM type
 */
enum TrackingEventStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Picking = 'picking';
    case Packed = 'packed';
    case PickedUp = 'picked_up';
    case Shipped = 'shipped';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Failed = 'failed';
}
